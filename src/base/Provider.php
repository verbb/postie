<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\events\ModifyPayloadEvent;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\base\SavableComponent;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response;

use craft\commerce\Plugin as Commerce;
use craft\commerce\records\ShippingRuleCategory;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

abstract class Provider extends SavableComponent implements ProviderInterface
{
    // Constants
    // =========================================================================

    const PERCENTAGE = 'percentage';
    const VALUE = 'value';

    const EVENT_MODIFY_RATES = 'modifyRates';
    const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    const EVENT_BEFORE_FETCH_RATES = 'beforeFetchRates';


    // Properties
    // =========================================================================

    public $name;
    public $enabled;
    public $settings = [];
    public $markUpRate;
    public $markUpBase;
    public $services = [];

    protected $_client;
    protected $_rates;
    protected $_cachedRates;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        // Populate and override provider settings from the plugin settings and config file
        foreach ($this->getSettings() as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getClass(): string
    {
        $nsClass = get_class($this);

        return substr($nsClass, strrpos($nsClass, "\\") + 1);
    }

    public function getHandle(): string
    {
        $class = $this->displayName();

        // Some special cases here, which is a bit annoying...
        // Refactor this as it's gross as...
        if ($class === 'USPS' || $class === 'UPS') {
            return strtolower($class);
        }

        if ($class === 'TNTAustralia') {
            return 'tntAustralia';
        }

        if ($class === 'DHLExpress') {
            return 'dhlExpress';
        }

        return StringHelper::toCamelCase($class);
    }

    public function getIconUrl()
    {
        try {
            $handle = strtolower($this->displayName());

            return Craft::$app->assetManager->getPublishedUrl('@verbb/postie/resources/dist/img/' . $handle . '.svg', true);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function isConfigured(): bool
    {
        $config = $this->getProviderConfig();

        if (!empty($config)) {
            return true;
        }

        return false;
    }

    public function getSettingsHtml()
    {
        return null;
    }

    public function getSettings(): array
    {
        // Get settings from the Class, Plugin Settings and Config file
        $settings = parent::getSettings();
        $config = Craft::$app->config->getConfigFromFile('postie');
        $pluginInfo = Craft::$app->plugins->getStoredPluginInfo('postie');

        $providerSettings = $pluginInfo['settings']['providers'][$this->getHandle()] ?? [];
        $configSettings = $config['providers'][$this->getHandle()] ?? [];

        $settings = array_merge(
            $settings,
            $providerSettings,
            $configSettings
        );

        // Ensure settings (settings) can be mixed in config and CP. This isn't automatically done becuase
        // they're nested array in the provider, so do them separately.
        $providerSettingsSettings = $providerSettings['settings'] ?? [];
        $configSettingsSettings = $configSettings['settings'] ?? [];

        $settings['settings'] = array_merge($providerSettingsSettings, $configSettingsSettings);

        // Special-case for services - these should be converted form key-value into ShippingMethods
        // Also helps with backwards compatibility and how services are stored in config files
        if (isset($settings['services'])) {
            foreach ($settings['services'] as $handle => $info) {
                // Create a temporary provider instance, just to pass to the shipping method
                // We need to be careful here so as not to cause an infinite loop.
                $tempProvider = clone $this;

                // Populate the temp provider with some stuff - just don't include services
                // that'll cause an infinite loop.
                $tempProvider->enabled = $settings['enabled'] ?? null;
                $tempProvider->settings = $settings['settings'] ?? null;
                $tempProvider->markUpRate = $settings['markUpRate'] ?? null;
                $tempProvider->markUpBase = $settings['markUpBase'] ?? null;

                $shippingMethod = new ShippingMethod();
                $shippingMethod->handle = $handle;
                $shippingMethod->provider = $tempProvider;

                // Stored in plugin settings as an array, config file as just the name
                if (is_array($info)) {
                    $shippingMethod->name = $info['name'];
                    $shippingMethod->enabled = $info['enabled'];

                    // Also sort out saved shipping categories
                    if (isset($info['shippingCategories'])) {
                        $ruleCategories = [];

                        foreach ($info['shippingCategories'] as $key => $ruleCategory) {
                            $ruleCategories[$key] = new ShippingRuleCategory($ruleCategory);
                            $ruleCategories[$key]->shippingCategoryId = $key;
                        }

                        $shippingMethod->shippingMethodCategories = $ruleCategories;
                    }
                } else {
                    $shippingMethod->name = $info;
                    $shippingMethod->enabled = true;
                }

                $settings['services'][$handle] = $shippingMethod;
            }
        }

        return $settings;
    }

    public function getMarkUpBaseOptions()
    {
        return [
            self::PERCENTAGE => StringHelper::toTitleCase(self::PERCENTAGE),
            self::VALUE => StringHelper::toTitleCase(self::VALUE),
        ];
    }

    public function getShippingMethods($order)
    {
        $shippingMethods = [];

        foreach ($this->services as $handle => $shippingMethod) {
            if ($shippingMethod->enabled) {
                $shippingMethods[$handle] = $shippingMethod;
            }
        }

        return $shippingMethods;
    }

    public function getShippingMethodByHandle($handle)
    {
        return $this->services[$handle] ?? [];
    }

    public function getShippingRates($order)
    {
        $settings = Postie::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        if (!$order) {
            Provider::log($this, 'Missing required order variable.');

            return;
        }

        if (!$order->getLineItems()) {
            Provider::log($this, 'No line items for order.');

            return;
        }

        if (!$order->shippingAddress) {
            Provider::log($this, 'No shipping address for order.');

            return;
        }

        $shippingRates = [];

        if ($settings->enableCaching) {        
            // Setup some caching mechanism to save API requests
            $signature = $this->getSignature($this->handle, $order);
            $cacheKey = 'postie-shipment-' . $signature;

            // Get the rate from the cache (if any)
            $shippingRates = Craft::$app->cache->get($cacheKey);

            // If is it not in the cache get rate via API
            if ($shippingRates === false) {
                $shippingRates = $this->prepareFetchShippingRates($order);

                // Set this in our cache for the next request to be much quicker
                if ($shippingRates) {
                    Craft::$app->cache->set($cacheKey, $shippingRates, 0);
                }
            }
        } else {
            $shippingRates = $this->prepareFetchShippingRates($order);
        }

        // Remove our session variable for fetching live rates manually (even if we're not opting to use it)
        if (!$request->getIsConsoleRequest()) {
            if (Craft::$app->getSession()->get('postieManualFetchRates')) {
                Craft::$app->getSession()->remove('postieManualFetchRates');
            }
        }

        return $shippingRates;
    }

    public function prepareFetchShippingRates($order)
    {
        $settings = Postie::$plugin->getSettings();
        $cachedRates = $this->_cachedRates[$this->handle] ?? [];
        $request = Craft::$app->getRequest();

        if (!$cachedRates) {
            // Check if we're manually fetching rates, only proceed if we are
            if ($settings->manualFetchRates && !Craft::$app->getSession()->get('postieManualFetchRates')) {
                // For CP requests, don't rely on the POST param and continue as normal
                if ($request->getIsSiteRequest()) {
                    Provider::log($this, 'Postie set to manually fetch rates. Required POST param not provided.');

                    return $cachedRates;
                }
            }

            $cachedRates = $this->_cachedRates[$this->handle] = $this->fetchShippingRates($order);
        }

        return $cachedRates;
    }

    public function getSignature($handle, $order)
    {
        $totalLength = 0;
        $totalWidth = 0;
        $totalHeight = 0;

        foreach ($order->lineItems as $key => $lineItem) {
            $totalLength += ($lineItem->qty * $lineItem->length);
            $totalWidth += ($lineItem->qty * $lineItem->width);
            $totalHeight += ($lineItem->qty * $lineItem->height);
        }

        $signature = implode('.', [
            $handle,
            $order->getTotalQty(),
            $order->getTotalWeight(),
            $totalWidth,
            $totalHeight,
            $totalLength,
            implode('.', $order->shippingAddress->addressLines),
        ]);

        return md5($signature);
    }


    // Static Methods
    // =========================================================================

    public static function log($provider, $message)
    {
        $isSiteRequest = Craft::$app->getRequest()->getIsSiteRequest();
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayDebug && $isSiteRequest) {
            Craft::dump($message);
        }

        Postie::log($message);
    }

    public static function error($provider, $message)
    {
        $isSiteRequest = Craft::$app->getRequest()->getIsSiteRequest();
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayErrors && $isSiteRequest) {
            Craft::dump($message);
        }

        if (Postie::$plugin->getSettings()->displayFlashErrors && $isSiteRequest) {
            Craft::$app->getSession()->setError($message);
        }

        Postie::error($message);
    }


    // Protected Methods
    // =========================================================================

    protected function getPackageDimensions($order)
    {
        $maxWidth = 0;
        $maxLength = 0;
        $totalHeight = 0;

        foreach ($order->lineItems as $key => $lineItem) {
            $maxLength = $maxLength < $lineItem->length ? $maxLength = $lineItem->length : $maxLength;
            $maxWidth = $maxWidth < $lineItem->width ? $maxWidth = $lineItem->width : $maxWidth;
            $totalHeight += ($lineItem->qty * $lineItem->height);
        }

        return [
            'length' => $maxWidth,
            'width'  => $maxLength,
            'height' => $totalHeight,
        ];
    }

    protected function getDimensions($order, $weightUnit, $dimensionUnit)
    {
        // Get Craft Commerce settings
        $settings = Commerce::getInstance()->getSettings();

        // Check for Craft Commerce weight settings
        $orderWeight = new Mass($order->getTotalWeight(), $settings->weightUnits);
        $weight = $orderWeight->toUnit($weightUnit);

        // Get box package dimensions based on order line items
        $packageDimensions = $this->getPackageDimensions($order);

        // Convert dimensions into unit we require
        $orderLength = new Length($packageDimensions['length'], $settings->dimensionUnits);
        $orderWidth = new Length($packageDimensions['width'], $settings->dimensionUnits);
        $orderHeight = new Length($packageDimensions['height'], $settings->dimensionUnits);
        
        $length = $orderLength->toUnit($dimensionUnit);
        $width = $orderWidth->toUnit($dimensionUnit);
        $height = $orderHeight->toUnit($dimensionUnit);

        return [
            'length' => $length,
            'width'  => $width,
            'height' => $height,
            'weight' => $weight,
        ];
    }

    protected function getSplitBoxWeights($value, $max)
    {
        $items = [];

        // Determine how many full-weight boxes we need
        for ($i = 1; $i <= ($value / $max); $i++) {
            $items[] = $max;
        }

        // Add in the remainder - if any
        if (fmod($value, $max)) {
            $items[] = fmod($value, $max);
        }

        return $items;
    }

    protected function beforeSendPayload($provider, &$payload, $order)
    {
        $event = new ModifyPayloadEvent([
            'provider' => $provider,
            'payload' => $payload,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_PAYLOAD)) {
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);
        }

        self::log($this, 'Sending payload: `' . json_encode($payload) . '`.');
    }

    protected function beforeFetchRates(&$storeLocation, &$dimensions, $order)
    {
        $fetchRatesEvent = new FetchRatesEvent([
            'storeLocation' => $storeLocation,
            'dimensions' => $dimensions,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_FETCH_RATES)) {
            $this->trigger(self::EVENT_BEFORE_FETCH_RATES, $fetchRatesEvent);
        }

        // Update back
        $storeLocation = $fetchRatesEvent->storeLocation;
        $dimensions = $fetchRatesEvent->dimensions;
    }
}
