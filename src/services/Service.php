<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\events\ModifyRateFingerprintEvent;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\helpers\ShippyHelper;
use verbb\postie\models\Rate;
use verbb\postie\models\Settings;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\Json;

use yii\base\Component;

use DateTimeInterface;
use Stringable;
use Throwable;
use WeakMap;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;
use verbb\shippy\Shippy;
use verbb\shippy\events\RateEvent;
use verbb\shippy\models\Shipment;

class Service extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_REGISTER_SHIPPING_METHODS = 'beforeRegisterShippingMethods';
    public const EVENT_MODIFY_RATE_FINGERPRINT = 'modifyRateFingerprint';

    private const RATE_CACHE_VERSION = 2;
    private const RATE_CACHE_PREFIX = 'postie-rates-v2:';


    // Properties
    // =========================================================================

    private ?WeakMap $_availableShippingMethods = null;


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

    public function createShippingMethodData(ShippingMethod $shippingMethod): array
    {
        return [
            'providerHandle' => $shippingMethod->provider?->handle,
            'serviceCode' => $shippingMethod->handle,
            'serviceName' => $shippingMethod->name,
            'rate' => $shippingMethod->rate,
            'rateOptions' => $shippingMethod->rateOptions ?? [],
        ];
    }

    public function createShippingMethodFromData(array $data): ?ShippingMethod
    {
        $providerHandle = $data['providerHandle'] ?? null;
        $serviceCode = $data['serviceCode'] ?? null;

        if (!is_string($providerHandle) || !is_string($serviceCode) || !isset($data['rate']) || !is_numeric($data['rate'])) {
            return null;
        }

        $providersService = Postie::$plugin->getProviders();
        $provider = $providersService->getProviderByHandle($providerHandle);

        if (!$provider || !$provider->getEnabled()) {
            return null;
        }

        $shippingMethod = $providersService->getShippingMethodForService($provider, $serviceCode);
        $shippingMethod->rate = isset($data['rate']) ? (float)$data['rate'] : null;
        $shippingMethod->rateOptions = is_array($data['rateOptions'] ?? null) ? $data['rateOptions'] : [];

        if (!$shippingMethod->name && is_string($data['serviceName'] ?? null)) {
            $shippingMethod->name = $data['serviceName'];
        }

        return $shippingMethod;
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

        // Completed orders should keep the checkout rate locked, unless an admin is explicitly
        // recalculating the order in the control panel (RECALCULATION_MODE_ALL).
        if ($order->isCompleted && $order->getRecalculationMode() !== Order::RECALCULATION_MODE_ALL) {
            $shippingMethods = $this->getShippingMethodsForCompletedOrder($order);
        } else {
            $shippingMethods = $this->_getShippingMethodsForActiveOrder($order, $settings);
        }

        // Allow plugins to modify the shipping methods.
        $modifyShippingMethodsEvent = new ModifyShippingMethodsEvent([
            'order' => $order,
            'shippingMethods' => $shippingMethods,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS)) {
            $this->trigger(self::EVENT_BEFORE_REGISTER_SHIPPING_METHODS, $modifyShippingMethodsEvent);
        }

        foreach ($modifyShippingMethodsEvent->shippingMethods as $shippingMethod) {
            // Integration-provided methods may not have a store yet, and Commerce requires one.
            $shippingMethod->storeId = Commerce::getInstance()->getStores()->getPrimaryStore()->id ?? null;

            $event->shippingMethods[] = $shippingMethod;
        }
    }


    // Private Methods
    // =========================================================================

    private function _getShippingMethodsForActiveOrder(Order $order, Settings $settings): array
    {
        $providers = Postie::$plugin->getProviders()->getAllEnabledProviders();
        $fingerprint = $this->_getRateFingerprint($order, $providers);
        $memoized = $this->_availableShippingMethods?->offsetExists($order) ? $this->_availableShippingMethods[$order] : null;

        if (($memoized['fingerprint'] ?? null) === $fingerprint && ($memoized['expiresAt'] ?? 0) > time() && !empty($memoized['rates']) && is_array($memoized['rates'])) {
            return $this->_createShippingMethodsFromData($memoized['rates']);
        }

        if ($memoized !== null) {
            unset($this->_availableShippingMethods[$order]);
        }

        $cacheKey = $order->uid ? self::RATE_CACHE_PREFIX . $order->uid : null;

        if ($settings->getEnableCaching() && $cacheKey) {
            $cachedEntry = $this->_getCachedRateEntry($cacheKey, $fingerprint);

            if ($cachedEntry !== null) {
                $this->_memoizeRates($order, $fingerprint, $cachedEntry['rates'], $cachedEntry['expiresAt']);

                return $this->_createShippingMethodsFromData($cachedEntry['rates'], true);
            }
        }

        if (!$settings->getEnableCaching() || !$cacheKey) {
            $shippingMethods = $this->getShippingMethodsForOrder($order);
            $rateData = $this->_createRateData($shippingMethods);

            if ($rateData) {
                $this->_memoizeRates($order, $fingerprint, $rateData, time() + $settings->getRateCacheDuration());
            }

            return $this->_createShippingMethodsFromData($rateData);
        }

        $mutex = Craft::$app->getMutex();
        $lockName = 'postie:rates:' . hash('sha256', $cacheKey . ':' . $fingerprint);

        if (!$mutex->acquire($lockName, 5)) {
            $cachedEntry = $this->_getCachedRateEntry($cacheKey, $fingerprint);

            return $cachedEntry === null ? [] : $this->_createShippingMethodsFromData($cachedEntry['rates'], true);
        }

        try {
            $cachedEntry = $this->_getCachedRateEntry($cacheKey, $fingerprint);

            if ($cachedEntry !== null) {
                $rateData = $cachedEntry['rates'];
                $expiresAt = $cachedEntry['expiresAt'];
                $fromCache = true;
            } else {
                $shippingMethods = $this->getShippingMethodsForOrder($order);
                $rateData = $this->_createRateData($shippingMethods);
                $expiresAt = time() + $settings->getRateCacheDuration();
                $fromCache = false;

                if ($rateData) {
                    Craft::$app->getCache()->set($cacheKey, [
                        'version' => self::RATE_CACHE_VERSION,
                        'fingerprint' => $fingerprint,
                        'rates' => $rateData,
                        'expiresAt' => $expiresAt,
                    ], $settings->getRateCacheDuration());
                }
            }

            if ($rateData) {
                $this->_memoizeRates($order, $fingerprint, $rateData, $expiresAt);
            }

            return $this->_createShippingMethodsFromData($rateData, $fromCache);
        } finally {
            $mutex->release($lockName);
        }
    }

    private function _getRateFingerprint(Order $order, array $providers): string
    {
        $commerceSettings = Commerce::getInstance()->getSettings();
        $providerConfigs = [];

        foreach ($providers as $provider) {
            $providerConfigs[] = [
                'handle' => $provider->handle,
                'digest' => $this->_hashFingerprintData(Postie::$plugin->getProviders()->createProviderConfig($provider), true),
            ];
        }

        $lineItems = [];

        foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
            $lineItems[] = PostieHelper::getLineItemFingerprintData($lineItem);
        }

        $data = [
            'version' => self::RATE_CACHE_VERSION,
            'order' => [
                'id' => $order->id,
                'uid' => $order->uid,
                'storeId' => $order->storeId,
                'siteId' => $order->siteId,
                'currency' => $order->currency,
                'email' => $order->email,
                'totalQty' => $order->getTotalQty(),
                'totalWeight' => $order->getTotalWeight(),
                'itemSubtotal' => $order->getItemSubtotal(),
                'totalDiscount' => $order->getTotalDiscount(),
                'fields' => $order->getSerializedFieldValues(),
                'lineItems' => $lineItems,
            ],
            'origin' => PostieHelper::getAddressLines(Postie::getStoreShippingAddress()),
            'destination' => PostieHelper::getAddressLines($order->getShippingAddress() ?? $order->getEstimatedShippingAddress()),
            'units' => [
                'dimension' => $commerceSettings->dimensionUnits,
                'weight' => $commerceSettings->weightUnits,
            ],
            'providers' => $providerConfigs,
            'runtime' => $this->_getRuntimeFingerprintData($providers),
        ];

        $event = new ModifyRateFingerprintEvent([
            'order' => $order,
            'fingerprintData' => $data,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_RATE_FINGERPRINT)) {
            $this->trigger(self::EVENT_MODIFY_RATE_FINGERPRINT, $event);
        }

        return $this->_hashFingerprintData($event->fingerprintData);
    }

    private function _getRuntimeFingerprintData(array $providers): array
    {
        foreach ($providers as $provider) {
            if (property_exists($provider, 'phoneField') && $provider->phoneField) {
                try {
                    return [
                        'cartFields' => Commerce::getInstance()->getCarts()->getCart()->getSerializedFieldValues(),
                    ];
                } catch (Throwable) {
                    return [];
                }
            }
        }

        return [];
    }

    private function _normalizeFingerprintValue(mixed $value, bool $parseEnv = false): mixed
    {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                ksort($value);
            }

            foreach ($value as $key => $item) {
                $value[$key] = $this->_normalizeFingerprintValue($item, $parseEnv);
            }

            return $value;
        }

        if (is_string($value)) {
            return $parseEnv ? App::parseEnv($value) : $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return $value;
    }

    private function _hashFingerprintData(array $data, bool $parseEnv = false): string
    {
        $normalizedData = $this->_normalizeFingerprintValue($data, $parseEnv);
        $encodedData = Json::encode($normalizedData, JSON_PRESERVE_ZERO_FRACTION);

        return hash('sha256', Craft::$app->getSecurity()->hashData($encodedData));
    }

    private function _getCachedRateEntry(string $cacheKey, string $fingerprint): ?array
    {
        $cached = Craft::$app->getCache()->get($cacheKey);

        if (!is_array($cached) || ($cached['version'] ?? null) !== self::RATE_CACHE_VERSION || ($cached['fingerprint'] ?? null) !== $fingerprint || !is_int($cached['expiresAt'] ?? null) || $cached['expiresAt'] <= time() || empty($cached['rates']) || !is_array($cached['rates'])) {
            return null;
        }

        return [
            'rates' => $cached['rates'],
            'expiresAt' => $cached['expiresAt'],
        ];
    }

    private function _memoizeRates(Order $order, string $fingerprint, array $rates, int $expiresAt): void
    {
        $this->_availableShippingMethods ??= new WeakMap();
        $this->_availableShippingMethods[$order] = [
            'fingerprint' => $fingerprint,
            'rates' => $rates,
            'expiresAt' => $expiresAt,
        ];
    }

    private function _createRateData(array $shippingMethods): array
    {
        $rateData = [];

        foreach ($shippingMethods as $shippingMethod) {
            $data = $this->createShippingMethodData($shippingMethod);

            if ($data['providerHandle'] && $data['serviceCode']) {
                $rateData[] = $data;
            }
        }

        return $rateData;
    }

    private function _createShippingMethodsFromData(array $rateData, bool $fromCache = false): array
    {
        $shippingMethods = [];

        foreach ($rateData as $data) {
            if (!is_array($data)) {
                continue;
            }

            $shippingMethod = $this->createShippingMethodFromData($data);

            if (!$shippingMethod) {
                continue;
            }

            if ($fromCache) {
                Postie::debugPaneLog('{provider}: Fetched rate `{rate}` for service `{service}` from cache.', [
                    'provider' => $shippingMethod->provider->name,
                    'service' => $shippingMethod->handle,
                    'rate' => $shippingMethod->rate,
                ]);
            }

            $shippingMethods[] = $shippingMethod;
        }

        return $shippingMethods;
    }

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

        if ($cachedShippingMethod instanceof ShippingMethod) {
            $cachedShippingMethod = $this->createShippingMethodFromData($this->createShippingMethodData($cachedShippingMethod));
        } elseif (is_array($cachedShippingMethod)) {
            $cachedShippingMethod = $this->createShippingMethodFromData($cachedShippingMethod);
        }

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
