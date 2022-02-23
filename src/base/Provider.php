<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\events\ModifyPayloadEvent;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\events\PackOrderEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\models\Box;
use verbb\postie\models\Item;
use verbb\postie\models\PackedBoxes;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\base\SavableComponent;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\records\ShippingRuleCategory;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

use DVDoug\BoxPacker\InfalliblePacker;
use DVDoug\BoxPacker\ItemTooLargeException;
use DVDoug\BoxPacker\PackedBox;
use DVDoug\BoxPacker\PackedBoxList;
use DVDoug\BoxPacker\PackedItem;
use DVDoug\BoxPacker\PackedItemList;

use Cake\Utility\Hash;

use Exception;

abstract class Provider extends SavableComponent implements ProviderInterface
{
    // Constants
    // =========================================================================

    const PERCENTAGE = 'percentage';
    const VALUE = 'value';

    const PACKING_PER_ITEM = 'perItem';
    const PACKING_BOX = 'boxPacking';
    const PACKING_SINGLE_BOX = 'singleBox';

    const EVENT_MODIFY_RATES = 'modifyRates';
    const EVENT_MODIFY_PAYLOAD = 'modifyPayload';
    const EVENT_BEFORE_FETCH_RATES = 'beforeFetchRates';
    const EVENT_BEFORE_PACK_ORDER = 'beforePackOrder';
    const EVENT_AFTER_PACK_ORDER = 'afterPackOrder';
    const EVENT_MODIFY_SHIPPING_METHODS = 'modifyShippingMethods';


    // Properties
    // =========================================================================

    public $name;
    public $handle;
    public $enabled;
    public $settings = [];
    public $markUpRate;
    public $markUpBase;
    public $services = [];
    public $restrictServices = true;
    public $packingMethod = self::PACKING_SINGLE_BOX;
    public $boxSizes = [];
    public $weightUnit = 'kg';
    public $dimensionUnit = 'cm';

    protected $_client;
    protected $_rates;
    protected $_cachedRates;


    // Static Methods
    // =========================================================================

    public static function log($provider, $message, $throwError = false)
    {
        $isSiteRequest = Craft::$app->getRequest()->getIsSiteRequest();
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayDebug && $isSiteRequest) {
            Craft::dump($message);
        }

        if ($throwError) {
            throw new Exception($message);
        }

        Postie::log($message);
    }

    public static function error($provider, $message, $throwError = false)
    {
        $isSiteRequest = Craft::$app->getRequest()->getIsSiteRequest();
        $message = $provider->name . ': ' . $message;

        if (Postie::$plugin->getSettings()->displayErrors && $isSiteRequest) {
            Craft::dump($message);
        }

        if (Postie::$plugin->getSettings()->displayFlashErrors && $isSiteRequest) {
            Craft::$app->getSession()->setError($message);
        }

        if ($throwError) {
            throw new Exception($message);
        }

        Postie::error($message);
    }

    public static function defineDefaultBoxes()
    {
        return [];
    }


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        // Setup default name + handle
        $this->name = $this->name ?? $this->displayName();
        $this->handle = $this->handle ?? StringHelper::toCamelCase($this->displayName());

        // Populate and override provider settings from the plugin settings and config file
        foreach ($this->getSettings() as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    public function getName(): string
    {
        return (string)$this->name;
    }

    public function getHandle(): string
    {
        return (string)$this->handle;
    }

    public function getClass(): string
    {
        $nsClass = get_class($this);

        return substr($nsClass, strrpos($nsClass, "\\") + 1);
    }

    public function getIconUrl(): string
    {
        try {
            $handle = StringHelper::toKebabCase($this->displayName());

            return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/postie/resources/dist/img/{$handle}.svg", true);
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

    public function supportsDynamicServices(): bool
    {
        return false;
    }

    public static function supportsConnection(): bool
    {
        return true;
    }

    public function getServiceList(): array
    {
        return [];
    }

    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("postie/providers/$handle", [
            'provider' => $this,
        ]);
    }

    public function getSettings(): array
    {
        // Get settings from the Class, Plugin Settings and Config file
        $settings = parent::getSettings();
        $config = Craft::$app->config->getConfigFromFile('postie');
        $pluginInfo = Craft::$app->plugins->getStoredPluginInfo('postie');

        $providerSettings = $pluginInfo['settings']['providers'][$this->handle] ?? [];
        $configSettings = $config['providers'][$this->handle] ?? [];

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
                $tempProvider->restrictServices = $settings['restrictServices'] ?? null;
                $tempProvider->packingMethod = $settings['packingMethod'] ?? null;
                $tempProvider->boxSizes = $settings['boxSizes'] ?? null;

                $shippingMethod = new ShippingMethod();
                $shippingMethod->handle = $handle;
                $shippingMethod->provider = $tempProvider;

                // Stored in plugin settings as an array, config file as just the name
                if (is_array($info)) {
                    $shippingMethod->name = $info['name'] ?? $this->getServiceList()[$handle] ?? '';
                    $shippingMethod->enabled = $info['enabled'] ?? '';

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

        // Add in any validation errors on the settings model, add them in
        if ($errors = Postie::$plugin->getSettings()->getErrors()) {
            $providerErrors = ArrayHelper::getValue(Hash::expand($errors), "providers.{$this->handle}.settings");

            if ($providerErrors) {
                $this->addErrors($providerErrors);

                // Ensure we return the in-memory version to retain the values we've entered
                $providerData = Postie::$plugin->getSettings()->providers[$this->handle] ?? [];

                // This is a bit horrendous. Refactor at a later stage to proper models.
                // Basically just add back in whatever we're validation (not much).
                $settings['boxSizes'] = $providerData['boxSizes'] ?? [];
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

    public function getWeightUnitOptions()
    {
        return [
            [ 'label' => Craft::t('commerce', 'Grams (g)'), 'value' => 'g' ],
            [ 'label' => Craft::t('commerce', 'Kilograms (kg)'), 'value' => 'kg' ],
            [ 'label' => Craft::t('commerce', 'Pounds (lb)'), 'value' => 'lb' ],
        ];
    }

    public function getDimensionUnitOptions()
    {
        return [
            [ 'label' => Craft::t('commerce', 'Millimeters (mm)'), 'value' => 'mm' ],
            [ 'label' => Craft::t('commerce', 'Centimeters (cm)'), 'value' => 'cm' ],
            [ 'label' => Craft::t('commerce', 'Meters (m)'), 'value' => 'm' ],
            [ 'label' => Craft::t('commerce', 'Feet (ft)'), 'value' => 'ft' ],
            [ 'label' => Craft::t('commerce', 'Inches (in)'), 'value' => 'in' ],
        ];
    }

    public function getShippingMethods($order)
    {
        $shippingMethods = [];

        if ($this->supportsDynamicServices()) {
            $shippingRates = $this->getShippingRates($order) ?? [];

            foreach (array_keys($shippingRates) as $key => $handle) {
                $shippingMethod = new ShippingMethod();
                $shippingMethod->handle = $handle;
                $shippingMethod->provider = $this;
                $shippingMethod->name = StringHelper::toTitleCase($handle);
                $shippingMethod->enabled = true;

                $shippingMethods[$handle] = $shippingMethod;
            }
        } else {
            foreach ($this->services as $handle => $shippingMethod) {
                if ($shippingMethod->enabled) {
                    $shippingMethods[$handle] = $shippingMethod;
                }

                // Force all to be enabled if not restricting
                if (!$this->restrictServices) {
                    $shippingMethod->enabled = true;

                    $shippingMethods[$handle] = $shippingMethod;
                }
            }
        }

        // Allow plugins to modify the shipping methods.
        $event = new ModifyShippingMethodsEvent([
            'provider' => $this,
            'order' => $order,
            'shippingMethods' => $shippingMethods,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_SHIPPING_METHODS)) {
            $this->trigger(self::EVENT_MODIFY_SHIPPING_METHODS, $event);
        }

        return $event->shippingMethods;
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

        if (!$order->shippingAddress && !$order->estimatedShippingAddress) {
            Provider::log($this, 'No shipping address for order.');

            return;
        }

        $shippingRates = [];

        if ($settings->enableCaching) {        
            // Setup some caching mechanism to save API requests
            $signature = PostieHelper::getSignature($order, $this->handle);
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
        $request = Craft::$app->getRequest();

        // Try and fetch rates based on the order signature right now.
        // This happens regardless of our global cache settings, because it's an in-memory, memoization
        // cache, but greatly improves performance, even when cache is turned off.
        $signature = PostieHelper::getSignature($order, $this->handle);
        $cachedRates = Postie::$plugin->getProviderCache()->getRates($signature);

        if ($cachedRates === null) {
            // Check if we're manually fetching rates, only proceed if we are
            if ($settings->manualFetchRates && !Craft::$app->getSession()->get('postieManualFetchRates')) {
                // For CP requests, don't rely on the POST param and continue as normal
                if ($request->getIsSiteRequest()) {
                    Provider::log($this, 'Postie set to manually fetch rates. Required POST param not provided.');

                    return [];
                }
            }

            // Fetch the rates
            $cachedRates = $this->fetchShippingRates($order);

            // If there are no rates returned, still cache the response, otherwise we likely hit API limits
            // repeatedly trying to fetch rates, when we know we won't get any, several times during checkout.
            // Ensure our falsy values are any empty array for our specific checks.
            if (!$cachedRates) {
                $cachedRates = [];
            }

            // Save the rates, globally for the entire request, not just this provider instance
            Postie::$plugin->getProviderCache()->setRates($signature, $cachedRates);
        }

        return $cachedRates;
    }

    public function getMaxPackageWeight($order)
    {
        return null;
    }

    public function getIsInternational($order): bool
    {
        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        $sourceCountry = $storeLocation->country->iso ?? '';
        $destinationCountry = $order->shippingAddress->country->iso ?? '';

        if ($storeLocation !== $destinationCountry) {
            return true;
        }

        return false;
    }

    public function getBoxSizesSettings()
    {
        return [
            'name' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Name'),
            ],
            'boxLength' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Length'),
                'placeholder' => Craft::t('postie', '{unit}', [ 'unit' => $this->dimensionUnit ]),
                'thin' => true,
            ],
            'boxWidth' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Width'),
                'placeholder' => Craft::t('postie', '{unit}', [ 'unit' => $this->dimensionUnit ]),
                'thin' => true,
            ],
            'boxHeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Height'),
                'placeholder' => Craft::t('postie', '{unit}', [ 'unit' => $this->dimensionUnit ]),
                'thin' => true,
            ],
            'boxWeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Weight'),
                'placeholder' => Craft::t('postie', '{unit}', [ 'unit' => $this->weightUnit ]),
                'thin' => true,
            ],
            'maxWeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Max Weight'),
                'placeholder' => Craft::t('postie', '{unit}', [ 'unit' => $this->weightUnit ]),
                'thin' => true,
            ],
            'enabled' => [
                'type' => 'lightswitch',
                'heading' => Craft::t('postie', 'Enabled'),
                'thin' => true,
                'small' => true,
            ],
            'default' => [
                'type' => 'hidden',
                'class' => 'hidden',
            ],
            'id' => [
                'type' => 'hidden',
                'class' => 'hidden',
            ],
        ];
    }

    public function getBoxSizesRows()
    {
        $boxSizes = [];

        if (!is_array($this->boxSizes)) {
            $this->boxSizes = [];
        }

        $defaultBoxes = static::defineDefaultBoxes();

        foreach ($defaultBoxes as $key => &$defaultBox) {
            $defaultBox['default'] = true;

            // Is this box already saved and has settings? We need to merge
            $savedData = ArrayHelper::firstWhere($this->boxSizes, 'id', $defaultBox['id']);

            if ($savedData) {
                $index = array_search($savedData, $this->boxSizes);

                // Directly update the default box data with the saved data.
                // This ensures the order defined in the provider is retained.
                $defaultBox = array_merge($defaultBox, $savedData);

                // Remove this from our saved data
                unset($this->boxSizes[$index]);
            }
        }

        $boxSizes = array_merge($defaultBoxes, $this->boxSizes);

        return $boxSizes;
    }

    public function checkConnection($useCache = true): bool
    {
        return $this->fetchConnection();
    }

    public function getIsConnected(): bool
    {
        $isConnected = $_COOKIE["postie-{$this->handle}-connect"] ?? null;

        return (bool)$isConnected;
    }

    public function getSetting($key)
    {
        $value = ArrayHelper::getValue($this->settings, $key) ?? null;

        if (!is_array($value)) {
            return Craft::parseEnv($value);
        }

        return $value;
    }


    // Protected Methods
    // =========================================================================

    protected function beforeSendPayload($provider, &$payload, $order)
    {
        $event = new ModifyPayloadEvent([
            'provider' => $provider,
            'payload' => $payload,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_PAYLOAD)) {
            $this->trigger(self::EVENT_MODIFY_PAYLOAD, $event);
        }

        // Apply the amended payload
        $payload = $event->payload;

        self::log($this, Craft::t('postie', 'Sending payload: `{json}`.', [
            'json' => Json::encode($payload),
        ]));
    }

    protected function beforeFetchRates(&$storeLocation, &$packedBoxes, $order)
    {
        $fetchRatesEvent = new FetchRatesEvent([
            'storeLocation' => $storeLocation,
            'order' => $order,
            'packedBoxes' => $packedBoxes,

            // Deprecated - todo remove at next breakpoint
            'dimensions' => $packedBoxes->getSerializedPackedBoxList(),
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_FETCH_RATES)) {
            $this->trigger(self::EVENT_BEFORE_FETCH_RATES, $fetchRatesEvent);
        }

        // Update back
        $storeLocation = $fetchRatesEvent->storeLocation;
    }

    protected function getLineItemDimensions($lineItem)
    {
        $settings = Commerce::getInstance()->getSettings();

        // We always deal with g/mm for box-packing, which is suitable for int's
        $weight = (new Mass($lineItem->weight, $settings->weightUnits))->toUnit('g');
        $length = (new Length($lineItem->length, $settings->dimensionUnits))->toUnit('mm');
        $width = (new Length($lineItem->width, $settings->dimensionUnits))->toUnit('mm');
        $height = (new Length($lineItem->height, $settings->dimensionUnits))->toUnit('mm');
        
        $dimensions = [
            'length' => $length,
            'width'  => $width,
            'height' => $height,
            'weight' => $weight,
        ];

        // Check if this line item actually has dimensions or weight
        if (!array_filter($dimensions)) {
            return false;
        }

        return $dimensions;
    }

    protected function getOrderDimensions($order, $weightUnit, $dimensionUnit)
    {
        $settings = Commerce::getInstance()->getSettings();

        $maxWidth = 0;
        $maxLength = 0;
        $totalHeight = 0;

        // Get box package dimensions based on order line items
        foreach ($order->lineItems as $key => $lineItem) {
            $maxLength = $maxLength < $lineItem->length ? $maxLength = $lineItem->length : $maxLength;
            $maxWidth = $maxWidth < $lineItem->width ? $maxWidth = $lineItem->width : $maxWidth;
            $totalHeight += ($lineItem->qty * $lineItem->height);
        }

        // We always deal with g/mm for box-packing, which is suitable for int's
        $weight = (new Mass($order->getTotalWeight(), $settings->weightUnits))->toUnit('g');
        $length = (new Length($maxLength, $settings->dimensionUnits))->toUnit('mm');
        $width = (new Length($maxWidth, $settings->dimensionUnits))->toUnit('mm');
        $height = (new Length($totalHeight, $settings->dimensionUnits))->toUnit('mm');

        return [
            'length' => $length,
            'width'  => $width,
            'height' => $height,
            'weight' => $weight,
        ];
    }

    protected function getBoxItemFromLineItem($lineItem)
    {
        $product = $lineItem->getPurchasable();

        if (!$product) {
            return false;
        }

        $dimensions = $this->getLineItemDimensions($lineItem);

        // Check if any dimensions are blank, return false
        if (!$dimensions) {
            return false;
        }

        return new Item([
            'description' => "Item {$lineItem->id}",
            'width' => $dimensions['width'],
            'length' => $dimensions['length'],
            'depth' => $dimensions['height'],
            'weight' => $dimensions['weight'],
            'itemValue' => $lineItem->price,
            'keepFlat' => false,
        ]);
    }

    protected function getBoxFromLineItem($lineItem)
    {
        $product = $lineItem->getPurchasable();

        if (!$product) {
            return false;
        }

        $dimensions = $this->getLineItemDimensions($lineItem);

        // Check if any dimensions are blank, return false
        if (!$dimensions) {
            return false;
        }


        return new Box([
            'reference' => "Box {$lineItem->id}",
            'outerWidth' => $dimensions['width'],
            'outerLength' => $dimensions['length'],
            'outerDepth' => $dimensions['height'],
            'emptyWeight' => 0,
            'innerWidth' => $dimensions['width'],
            'innerLength' => $dimensions['length'],
            'innerDepth' => $dimensions['height'],
            'maxWeight' => $dimensions['weight'],
        ]);
    }

    protected function getBoxSizes()
    {
        $boxes = [];

        // Because our box-packer only deals with integers, we should convert whatever unit this provider is using
        // (which is the same as what unit boxes are created in) and convert to g/mm to resolve as int's effectively.
        foreach ($this->getBoxSizesRows() as $i => $boxInfo) {
            if (!(bool)$boxInfo['enabled']) {
                continue;
            }

            $boxInfo['maxWeight'] = (new Mass($boxInfo['maxWeight'], $this->weightUnit))->toUnit('g');
            $boxInfo['boxWeight'] = (new Mass($boxInfo['boxWeight'], $this->weightUnit))->toUnit('g');
            $boxInfo['boxLength'] = (new Length($boxInfo['boxLength'], $this->dimensionUnit))->toUnit('mm');
            $boxInfo['boxWidth'] = (new Length($boxInfo['boxWidth'], $this->dimensionUnit))->toUnit('mm');
            $boxInfo['boxHeight'] = (new Length($boxInfo['boxHeight'], $this->dimensionUnit))->toUnit('mm');

            $boxes[] = $boxInfo;
        }

        return $boxes;
    }

    protected function packOrder(Order $order)
    {
        $packer = new InfalliblePacker();

        $packOrderEvent = new PackOrderEvent([
            'packer' => $packer,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_PACK_ORDER)) {
            $this->trigger(self::EVENT_BEFORE_PACK_ORDER, $packOrderEvent);
        }

        if ($this->packingMethod === self::PACKING_SINGLE_BOX) {
            $dimensions = $this->getOrderDimensions($order, $this->weightUnit, $this->dimensionUnit);

            // Let providers define the max weight for boxes
            $maxWeight = $this->getMaxPackageWeight($order) ?? $dimensions['weight'];

            $packer->addBox(new Box([
                'reference' => "Single Box",
                'outerWidth' => $dimensions['width'],
                'outerLength' => $dimensions['length'],
                'outerDepth' => $dimensions['height'],
                'emptyWeight' => 0,
                'innerWidth' => $dimensions['width'],
                'innerLength' => $dimensions['length'],
                'innerDepth' => $dimensions['height'],
                'maxWeight' => $maxWeight,
            ]));

            foreach ($order->getLineItems() as $lineItem) {
                if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                    $packer->addItem($boxItem, $lineItem->qty);
                }
            }
        }

        // If packing boxes individually, create boxes exactly the same size as each item
        if ($this->packingMethod === self::PACKING_PER_ITEM) {
            foreach ($order->getLineItems() as $lineItem) {
                // Don't forget to factor in quantities
                for ($i = 0; $i < $lineItem->qty; $i++) { 
                    // Generate a box for each item. It'll be exactly fitted to the item
                    if ($box = $this->getBoxFromLineItem($lineItem)) {
                        $packer->addBox($box);
                    }

                    // Add the single item to the single box
                    if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                        $packer->addItem($boxItem, 1);
                    }
                }
            }
        }

        // Run 4D bin-packing to the best of our ability
        if ($this->packingMethod === self::PACKING_BOX) {
            // For all boxes we've defined, add them.
            foreach ($this->getBoxSizes() as $boxInfo) {
                $packer->addBox(new Box([
                    'reference' => $boxInfo['name'],
                    'outerWidth' => $boxInfo['boxWidth'],
                    'outerLength' => $boxInfo['boxLength'],
                    'outerDepth' => $boxInfo['boxHeight'],
                    'emptyWeight' => $boxInfo['boxWeight'],
                    'innerWidth' => $boxInfo['boxWidth'],
                    'innerLength' => $boxInfo['boxLength'],
                    'innerDepth' => $boxInfo['boxHeight'],
                    'maxWeight' => $boxInfo['maxWeight'],

                    // Optional - for some providers
                    'type' => $boxInfo['boxType'] ?? '',
                ]));
            }

            // For each item in the cart, add them to the packer to figure out the best fit
            foreach ($order->getLineItems() as $lineItem) {
                if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                    $packer->addItem($boxItem, $lineItem->qty);
                }
            }
        }

        // Get a collection of packed boxes
        $packedBoxes = $packer->pack();

        // Any unpacked items need to be re-packed with their own individual boxes. This is to ensure that no
        // large items get left behind in shipping calculations.
        if ($unpackedItems = $packer->getUnpackedItems()) {
            foreach ($unpackedItems as $i => $unpackedItem) {
                // Create a single box, and create a packed item manually
                $box = new Box([
                    'reference' => "Extra Box {$i}",
                    'outerWidth' => $unpackedItem->getWidth(),
                    'outerLength' => $unpackedItem->getLength(),
                    'outerDepth' => $unpackedItem->getDepth(),
                    'emptyWeight' => 0,
                    'innerWidth' => $unpackedItem->getWidth(),
                    'innerLength' => $unpackedItem->getLength(),
                    'innerDepth' => $unpackedItem->getDepth(),
                    'maxWeight' => $unpackedItem->getWeight(),
                ]);

                $packedItem = new PackedItem($unpackedItem, 0, 0, 0, $unpackedItem->getWidth(), $unpackedItem->getLength(), $unpackedItem->getDepth());
                
                // Create a packed list
                $packedItemList = new PackedItemList();
                $packedItemList->insert($packedItem);

                // Add the list items to the box
                $packedBox = new PackedBox($box, $packedItemList);

                // Add the packed box to any other boxes, natively packed.
                $packedBoxes->insert($packedBox);
            }
        }

        if (!$packedBoxes) {
            self::error(Craft::t('postie', 'Unable to pack order for “{pack}”.', ['pack' => $this->packingMethod]));
        }

        $packedBoxes = new PackedBoxes($packedBoxes, $this->weightUnit, $this->dimensionUnit);

        $packOrderEvent = new PackOrderEvent([
            'packer' => $packer,
            'order' => $order,
            'packedBoxes' => $packedBoxes,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_PACK_ORDER)) {
            $this->trigger(self::EVENT_AFTER_PACK_ORDER, $packOrderEvent);
        }

        return $packOrderEvent->packedBoxes;
    }
}
