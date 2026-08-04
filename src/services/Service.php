<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\helpers\ShippyHelper;
use verbb\postie\models\Rate;
use verbb\postie\models\Settings;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\elements\Address;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;

use yii\base\Component;

use verbb\shippy\Shippy;
use verbb\shippy\events\RateEvent;
use verbb\shippy\models\Shipment;

class Service extends Component
{
    // Constants
    // =========================================================================
    
    public const EVENT_BEFORE_REGISTER_SHIPPING_METHODS = 'beforeRegisterShippingMethods';


    // Properties
    // =========================================================================

    private ?array $_availableShippingMethods = null;


    // Public Methods
    // =========================================================================

    public function getPrimaryStoreLocation(): ?Address
    {
        return Commerce::getInstance()->getStores()->getCurrentStore()->getSettings()->getLocationAddress() ?? null;
    }

    public function getShippyShipmentForOrder(Order $order): ?Shipment
    {
        // Allow easy-testing of addresses at the plugin level
        $storeLocation = Postie::getStoreShippingAddress();

        // Allow easy-testing of addresses at the plugin level
        Postie::setOrderShippingAddress($order);

        // Shipping address can be the estimated address too
        $shippingAddress = $order->getShippingAddress() ?? $order->getEstimatedShippingAddress();

        // Set the Shippy logger for consolidated logging with Postie
        if (($logTarget = (Craft::$app->getLog()->targets['postie'] ?? null))) {
            Shippy::setLogger($logTarget->getLogger());
        }

        if (!$storeLocation || !$shippingAddress) {
            return null;
        }

        // Create a Shippy shipment first for the origin/destination
        return new Shipment([
            'currency' => $order->currency,
            'from' => ShippyHelper::toAddress($order, $storeLocation),
            'to' => ShippyHelper::toAddress($order, $shippingAddress),
        ]);
    }

    /**
     * @return ShippingMethod[]
     */
    public function getShippingMethodsForOrder(Order $order): array
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        // Check if this route is enabled to fetch rates on. We're pretty guarded for rate-fetching for good reason.
        if ($settings->getEnableRouteCheck()) {
            if (!$settings->hasMatchedRoute()) {
                if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                    return [];
                }

                Postie::debugPaneLog('Route `{route}` did not match required route to fetch rates.', ['route' => Craft::$app->getRequest()->url]);

                return [];
            }
        }

        $shippingMethods = [];

        $providersService = Postie::$plugin->getProviders();
        $providers = $providersService->getAllEnabledProviders();

        // Create a Shippy shipment to start getting rates for
        $shipment = $this->getShippyShipmentForOrder($order);

        if (!$shipment) {
            Postie::debugPaneLog('Unable to create Shipment for order.');

            return [];
        }

        foreach ($providers as $provider) {
            // Prepare the shipment based on the provider
            $provider->prepareForShippy($shipment, $order);

            $carrier = $provider->getCarrier();

            // Attach event handlers for Craft
            $carrier->on($carrier::EVENT_BEFORE_FETCH_RATES, function(RateEvent $event) use ($provider, $order) {
                $provider->beforeFetchRates($event, $order);
            });

            $carrier->on($carrier::EVENT_AFTER_FETCH_RATES, function(RateEvent $event) use ($provider, $order) {
                $provider->afterFetchRates($event, $order);
            });
        }

        // Actually fetch the rates
        $rateResponse = $shipment->getRates();

        // Convert all rates into shipping methods
        foreach ($rateResponse->getRates() as $rate) {
            // We've stored the provider against the rate's carrier, so we can make use of Shippy's rate consolidation
            $provider = $rate->getCarrier()->getSetting('provider');

            // Get the shipping method with our overrides for name, etc
            $shippingMethod = $providersService->getShippingMethodForService($provider, $rate->getServiceCode());
            $shippingMethod->rate = $rate->getRate();
            $shippingMethod->rateOptions = $rate->getResponse();

            if (!$shippingMethod->name) {
                $shippingMethod->name = $rate->getServiceName();
            }

            $shippingMethods[] = $shippingMethod;
        }

        return $shippingMethods;
    }

    public function registerShippingMethods(RegisterAvailableShippingMethodsEvent $event): void
    {
        $order = $event->order;

        if (!$order || !$order->getLineItems()) {
            return;
        }

        // Allow easy-testing of addresses at the plugin level
        Postie::setOrderShippingAddress($order);

        if (!$order->shippingAddress && !$order->estimatedShippingAddress) {
            Postie::info('No shipping address for order.');

            return;
        }

        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        // Because this function can be called multiple times, save available methods to a local cache
        if ($this->_availableShippingMethods === null) {
            // Completed orders should keep the checkout rate locked, unless an admin is explicitly
            // recalculating the order in the control panel (RECALCULATION_MODE_ALL).
            if ($order->isCompleted && $order->getRecalculationMode() !== Order::RECALCULATION_MODE_ALL) {
                $this->_availableShippingMethods = $this->getShippingMethodsForCompletedOrder($order);
            } else if ($settings->getEnableCaching()) {
                $signature = PostieHelper::getSignature($order);
                $cacheKey = 'postie-shipment-' . $signature;

                // Get the rate from the cache (if any)
                $cachedShippingMethods = Craft::$app->getCache()->get($cacheKey);

                // If is it not in the cache get rate via API
                if ($cachedShippingMethods === false) {
                    $this->_availableShippingMethods = $this->getShippingMethodsForOrder($order);

                    // Set this in our cache for the next request to be much quicker
                    if ($this->_availableShippingMethods) {
                        Craft::$app->getCache()->set($cacheKey, $this->_availableShippingMethods, 0);
                    }
                } else {
                    foreach ($cachedShippingMethods as $method) {
                        Postie::debugPaneLog('{provider}: Fetched rate `{rate}` for service `{service}` from cache.', [
                            'provider' => $method->provider->name,
                            'service' => $method->handle,
                            'rate' => $method->rate,
                        ]);
                    }

                    $this->_availableShippingMethods = $cachedShippingMethods;
                }
            } else {
                $this->_availableShippingMethods = $this->getShippingMethodsForOrder($order);
            }
        }

        $this->_availableShippingMethods = $this->_availableShippingMethods ?? [];

        // Allow plugins to modify the shipping methods.
        $modifyShippingMethodsEvent = new ModifyShippingMethodsEvent([
            'order' => $order,
            'shippingMethods' => $this->_availableShippingMethods,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS)) {
            $this->trigger(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS, $modifyShippingMethodsEvent);
        }

        foreach ($modifyShippingMethodsEvent->shippingMethods as $shippingMethod) {
            // Ensure that the shipping method has `storeId` set, otherwise a fatal error will be thrown.
            // This can be removed at the next breakpoint, as it's already done when creating a new `ShippingMethod` but
            // because these objects are cached, this won't be populated, so it's set here for safety for everyone.
            $shippingMethod->storeId = Commerce::getInstance()->getStores()->getPrimaryStore()->id ?? null;

            $event->shippingMethods[] = $shippingMethod;
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * Return the shipping method locked in at checkout for a completed order.
     *
     * Prefers the rate stored in `postie_rates`, then the checkout shipping-method cache,
     * then Commerce's `storedTotalShippingCost` for a matching Postie service handle.
     *
     * @return ShippingMethod[]
     */
    private function getShippingMethodsForCompletedOrder(Order $order): array
    {
        if (!$order->shippingMethodHandle) {
            return [];
        }

        $providersService = Postie::$plugin->getProviders();
        $storedRate = $this->_getStoredRateForOrder($order);

        if ($storedRate) {
            $provider = $storedRate->getProvider();

            if ($provider) {
                $shippingMethod = $providersService->getShippingMethodForService($provider, $storedRate->service);
                $shippingMethod->rate = (float)$storedRate->rate;
                $shippingMethod->rateOptions = $this->_rateOptionsFromStoredRate($storedRate);

                if (!$shippingMethod->name && $order->shippingMethodName) {
                    $shippingMethod->name = $order->shippingMethodName;
                }

                Postie::debugPaneLog('{provider}: Using stored rate `{rate}` for completed order service `{service}`.', [
                    'provider' => $provider->name,
                    'service' => $shippingMethod->handle,
                    'rate' => $shippingMethod->rate,
                ]);

                return [$shippingMethod];
            }
        }

        // Fall back to the shipping method cached during checkout, if still available
        $cacheKey = 'postie-shipping-method:' . $order->uid;
        $cachedShippingMethod = Craft::$app->getCache()->get($cacheKey);

        if ($cachedShippingMethod instanceof ShippingMethod && $cachedShippingMethod->handle === $order->shippingMethodHandle) {
            Postie::debugPaneLog('{provider}: Using checkout-cached rate `{rate}` for completed order service `{service}`.', [
                'provider' => $cachedShippingMethod->provider->name ?? 'Postie',
                'service' => $cachedShippingMethod->handle,
                'rate' => $cachedShippingMethod->rate,
            ]);

            return [$cachedShippingMethod];
        }

        // Last resort: resolve the Postie service from the order handle and use Commerce's stored shipping total
        foreach ($providersService->getAllEnabledProviders() as $provider) {
            if (!isset($provider->services[$order->shippingMethodHandle])) {
                continue;
            }

            $shippingMethod = $providersService->getShippingMethodForService($provider, $order->shippingMethodHandle);
            $shippingMethod->rate = (float)$order->storedTotalShippingCost;
            $shippingMethod->rateOptions = [];

            if (!$shippingMethod->name && $order->shippingMethodName) {
                $shippingMethod->name = $order->shippingMethodName;
            }

            Postie::debugPaneLog('{provider}: Using order storedTotalShippingCost `{rate}` for completed order service `{service}`.', [
                'provider' => $provider->name,
                'service' => $shippingMethod->handle,
                'rate' => $shippingMethod->rate,
            ]);

            return [$shippingMethod];
        }

        Postie::debugPaneLog('No locked Postie shipping method found for completed order `{number}`.', [
            'number' => $order->number,
        ]);

        return [];
    }

    private function _getStoredRateForOrder(Order $order): ?Rate
    {
        $rates = Postie::$plugin->getRates()->getRatesByOrderId((int)$order->id);

        if (!$rates) {
            return null;
        }

        foreach ($rates as $rate) {
            if ($rate->service === $order->shippingMethodHandle) {
                return $rate;
            }
        }

        // Orders should only have one Postie rate, so fall back to the latest saved row
        return end($rates) ?: null;
    }

    private function _rateOptionsFromStoredRate(Rate $rate): array
    {
        $response = $rate->response;

        if (is_string($response) && $response !== '') {
            $response = Json::decodeIfJson($response);
        }

        return is_array($response) ? $response : [];
    }
}
