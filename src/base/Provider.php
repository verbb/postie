<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
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
    protected static $_cachedRates = [];


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

        return StringHelper::toCamelCase($class);
    }

    public function getIconUrl()
    {
        $handle = strtolower($this->displayName());

        return Craft::$app->assetManager->getPublishedUrl('@verbb/postie/resources/dist/img/' . $handle . '.svg', true);
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

        // Special-case for services - these should be converted form key-value into ShippingMethods
        // Also helps with backwards compatibility and how services are stored in config files
        if (isset($settings['services'])) {
            foreach ($settings['services'] as $handle => $info) {
                $shippingMethod = new ShippingMethod();
                $shippingMethod->handle = $handle;
                $shippingMethod->provider = $this;

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

    public function getShippingMethods()
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

        return $shippingRates;
    }

    public function prepareFetchShippingRates($order)
    {
        if (!self::$_cachedRates) {
            self::$_cachedRates = $this->fetchShippingRates($order);
        }

        return self::$_cachedRates;
    }


    // Static Methods
    // =========================================================================

    public static function log($provider, $message)
    {
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayDebug) {
            Craft::dump($message);
        }

        Postie::log($message);
    }

    public static function error($provider, $message)
    {
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayErrors) {
            Craft::dump($message);
        }

        Postie::error($message);
    }


    // Protected Methods
    // =========================================================================

    protected function getSignature($handle, $order)
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
            implode('.', $order->shippingAddress->toArray()),
        ]);

        return md5($signature);
    }

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
            'length' => (int)$maxWidth,
            'width'  => (int)$maxLength,
            'height' => (int)$totalHeight,
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
            'length' => (int)$length,
            'width'  => (int)$width,
            'height' => (int)$height,
            'weight' => (float)$weight,
        ];
    }
}
