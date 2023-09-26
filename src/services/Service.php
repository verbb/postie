<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\helpers\ShippyHelper;
use verbb\postie\models\Settings;
use verbb\postie\models\ShippingMethod;

use Craft;

use craft\commerce\elements\Order;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;

use yii\base\Component;

use verbb\shippy\Shippy;
use verbb\shippy\models\Package;
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

    /**
     * @return ShippingMethod[]
     */
    public function getShippingMethodsForOrder(Order $order): array
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        // Check if this route is enabled to fetch rates on. We're pretty guarded for rate-fetching for good reason.
        if ($settings->enableRouteCheck) {
            if (!$settings->hasMatchedRoute()) {
                Postie::debugPaneLog('Route `{route}` did not match required route to fetch rates.', ['route' => Craft::$app->getRequest()->url]);

                return [];
            }
        }

        $shippingMethods = [];

        $providersService = Postie::$plugin->getProviders();
        $providers = $providersService->getAllEnabledProviders();

        // Allow easy-testing of addresses at the plugin level
        $storeLocation = Postie::getStoreShippingAddress();

        // Set the Shippy logger for consolidated logging with Postie
        if (($logTarget = (Craft::$app->getLog()->targets['postie'] ?? null))) {
            Shippy::setLogger($logTarget->getLogger());
        }

        // Create a Shippy shipment first for the origin/destination
        $shipment = new Shipment([
            'currency' => $order->currency,
            'from' => ShippyHelper::toAddress($order, $storeLocation),
            'to' => ShippyHelper::toAddress($order, $order->shippingAddress),
        ]);

        foreach ($providers as $provider) {
            $carrier = $provider->getCarrier();

            // Add all the carriers we want to fetch rates for
            $shipment->addCarrier($carrier);

            // Attach event handlers for Craft
            $carrier->on($carrier::EVENT_BEFORE_FETCH_RATES, [$provider, 'beforeFetchRates']);
            $carrier->on($carrier::EVENT_AFTER_FETCH_RATES, [$provider, 'afterFetchRates']);

            // Allow providers to pack the order, if they have specific boxes or just using the line items
            $packedBoxes = $provider->packOrder($order);

            // Convert Postie packed boxes to Shippy packages
            foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                $shipment->addPackage(new Package([
                    'length' => $packedBox['length'],
                    'width' => $packedBox['width'],
                    'height' => $packedBox['height'],
                    'weight' => $packedBox['weight'],
                    'price' => $packedBox['price'],
                    'packageType' => $packedBox['type'],
                    'dimensionUnit' => $packedBoxes->getDimensionUnit(),
                    'weightUnit' => $packedBoxes->getWeightUnit(),
                ]));
            }
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
            Postie::log('No shipping address for order.');

            return;
        }

        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        // Setup some caching mechanism to save API requests
        if ($settings->enableCaching) {
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
                // Output info to the debug panel for clarity. Only print it once, as this is called multiple times
                if ($this->_availableShippingMethods === null) {
                    foreach ($cachedShippingMethods as $method) {
                        Postie::debugPaneLog('{provider}: Fetched rate `{rate}` for service `{service}` from cache.', [
                            'provider' => $method->provider->name,
                            'service' => $method->handle,
                            'rate' => $method->rate,
                        ]);
                    }
                }

                $this->_availableShippingMethods = $cachedShippingMethods;
            }
        }

        // Because this function can be called multiple times, save available methods to a local cache
        if ($this->_availableShippingMethods === null) {
            $this->_availableShippingMethods = $this->getShippingMethodsForOrder($order);
        }

        // Allow plugins to modify the shipping methods.
        $modifyShippingMethodsEvent = new ModifyShippingMethodsEvent([
            'order' => $order,
            'shippingMethods' => $this->_availableShippingMethods,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS)) {
            $this->trigger(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS, $modifyShippingMethodsEvent);
        }

        foreach ($modifyShippingMethodsEvent->shippingMethods as $shippingMethod) {
            $event->shippingMethods[] = $shippingMethod;
        }
    }
}
