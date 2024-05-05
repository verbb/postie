<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\events\PackOrderEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\helpers\ShippyHelper;
use verbb\postie\helpers\TestingHelper;
use verbb\postie\models\Box;
use verbb\postie\models\Item;
use verbb\postie\models\PackedBoxes;

use Craft;
use craft\base\SavableComponent;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

use DVDoug\BoxPacker\InfalliblePacker;
use DVDoug\BoxPacker\PackedBox;
use DVDoug\BoxPacker\PackedItem;
use DVDoug\BoxPacker\PackedItemList;

use verbb\shippy\Shippy;
use verbb\shippy\carriers\CarrierInterface;
use verbb\shippy\events\RateEvent;
use verbb\shippy\models\Address as ShippyAddress;
use verbb\shippy\models\LabelResponse;
use verbb\shippy\models\Package;
use verbb\shippy\models\Rate;
use verbb\shippy\models\RateResponse;
use verbb\shippy\models\Shipment;

use Throwable;

abstract class Provider extends SavableComponent implements ProviderInterface
{
    // Constants
    // =========================================================================

    public const API_RATES = 'rates';
    public const API_TRACKING = 'tracking';
    public const API_SHIPPING = 'shipping';

    public const PERCENTAGE = 'percentage';
    public const VALUE = 'value';

    public const PACKING_PER_ITEM = 'perItem';
    public const PACKING_BOX = 'boxPacking';
    public const PACKING_SINGLE_BOX = 'singleBox';

    public const EVENT_BEFORE_FETCH_RATES = 'beforeFetchRates';
    public const EVENT_AFTER_FETCH_RATES = 'afterFetchRates';
    public const EVENT_BEFORE_PACK_ORDER = 'beforePackOrder';
    public const EVENT_AFTER_PACK_ORDER = 'afterPackOrder';


    // Static Methods
    // =========================================================================

    public static function defineDefaultBoxes(): array
    {
        return [];
    }

    public static function getServiceList(): array
    {
        return static::getCarrierClass()::getServiceCodes();
    }

    public static function getWeightUnit(): string
    {
        // Use a fake shipment to resolve the correct country for units
        $storeLocation = Postie::getStoreShippingAddress();

        $shipment = new Shipment([
            'from' => new ShippyAddress([
                'countryCode' => $storeLocation->countryCode,
            ]),
        ]);

        return static::getCarrierClass()::getWeightUnit($shipment);
    }

    public static function getDimensionUnit(): string
    {
        // Use a fake shipment to resolve the correct country for units
        $storeLocation = Postie::getStoreShippingAddress();

        $shipment = new Shipment([
            'from' => new ShippyAddress([
                'countryCode' => $storeLocation->countryCode,
            ]),
        ]);

        return static::getCarrierClass()::getDimensionUnit($shipment);
    }



    // Abstract Methods
    // =========================================================================
    
    abstract public static function getCarrierClass(): string;


    // Properties
    // =========================================================================

    public ?string $name = null;
    public ?string $handle = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;
    public ?float $markUpRate = null;
    public ?string $markUpBase = null;
    public bool $restrictServices = true;
    public array $services = [];
    public string $packingMethod = self::PACKING_SINGLE_BOX;
    public array $boxSizes = [];
    public ?string $apiType = null;

    private bool|string $_enabled = true;
    private bool|string $_isProduction = false;
    private CarrierInterface|null $_carrier = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->name;
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['boxSizes', 'validateBoxSizes', 'skipOnEmpty' => false, 'skipOnError' => false];

        return $rules;
    }

    public function validateBoxSizes($attribute, $params, $validator): void
    {
        if ($this->packingMethod === self::PACKING_BOX) {
            if ($this->$attribute) {
                $enabledBoxes = ArrayHelper::where($this->$attribute, 'enabled');

                if (!$enabledBoxes) {
                    $this->addError($attribute, Craft::t('postie', 'You must provide at least one enabled box.'));
                }

                foreach ($enabledBoxes as $k => $box) {
                    $name = $box['name'] ?? '';
                    $boxLength = $box['boxLength'] ?? '';
                    $boxWidth = $box['boxWidth'] ?? '';
                    $boxHeight = $box['boxHeight'] ?? '';
                    $boxWeight = $box['boxWeight'] ?? '';
                    $maxWeight = $box['maxWeight'] ?? '';
                    $enabled = $box['enabled'] ?? '';
                    $default = $box['default'] ?? '';

                    if ($name === '' || $boxLength === '' || $boxWidth === '' || $boxHeight === '' || $boxWeight === '' || $maxWeight === '') {
                        $this->addError($attribute, Craft::t('postie', 'You must provide values for all fields.'));

                        break;
                    }
                }
            } else {
                $this->addError($attribute, Craft::t('postie', 'You must provide at least one box.'));
            }
        }
    }

    public function getEnabled(bool $parse = true): bool|string
    {
        if ($parse) {
            return App::parseBooleanEnv($this->_enabled) ?? true;
        }

        return $this->_enabled;
    }

    public function setEnabled(bool|string $name): void
    {
        $this->_enabled = $name;
    }

    public function isProduction(bool $parse = true): bool|string
    {
        if ($parse) {
            return App::parseBooleanEnv($this->_isProduction) ?? true;
        }

        return $this->_isProduction;
    }

    public function setIsProduction(bool|string $name): void
    {
        $this->_isProduction = $name;
    }

    public function getApiType(): ?string
    {
        return App::parseEnv($this->apiType);
    }

    public function getIconUrl(): string
    {
        try {
            $handle = StringHelper::toKebabCase(self::displayName());

            return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/postie/resources/dist/img/{$handle}.svg", true);
        } catch (Throwable $e) {
            return '';
        }
    }

    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(self::displayName());

        return Craft::$app->getView()->renderTemplate("postie/providers/_includes/$handle", [
            'provider' => $this,
        ]);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('postie/providers/edit/' . $this->id);
    }

    public function getMarkUpBaseOptions(): array
    {
        return [
            self::PERCENTAGE => StringHelper::toTitleCase(self::PERCENTAGE),
            self::VALUE => StringHelper::toTitleCase(self::VALUE),
        ];
    }

    public function getCarrier(): CarrierInterface
    {
        if ($this->_carrier !== null) {
            return $this->_carrier;
        }

        $className = static::getCarrierClass();

        return $this->_carrier = new $className($this->getCarrierConfig());
    }

    public function getCarrierConfig(): array
    {
        $services = [];

        if ($this->restrictServices) {
            $services = array_filter($this->services, function($service) {
                return $service['enabled'];
            });
        }

        return [
            'isProduction' => false,
            'allowedServiceCodes' => array_keys($services),
            'settings' => [
                'provider' => $this,
            ],
        ];
    }

    public function prepareForShippy(Shipment $shipment, Order $order): void
    {
        // Add the carrier we want to fetch rates for
        $shipment->addCarrier($this->getCarrier());

        // Allow providers to pack the order, if they have specific boxes or just using the line items
        $packedBoxes = $this->packOrder($order);

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

    public function getLabels(Order $order, string $serviceCode): ?LabelResponse
    {
        // Create a Shippy shipment to get labels for
        $shipment = Postie::$plugin->getService()->getShippyShipmentForOrder($order);

        // Prepare the shipment based on the provider
        $this->prepareForShippy($shipment, $order);

        $carrier = $this->getCarrier();

        // Attach event handlers for Craft
        $carrier->on($carrier::EVENT_BEFORE_FETCH_LABELS, [$this, 'beforeFetchLabels']);
        $carrier->on($carrier::EVENT_AFTER_FETCH_LABELS, [$this, 'afterFetchLabels']);

        // Create a new rate with the supplied service. No need to re-fetch the rate
        $rate = new Rate([
            'carrier' => $carrier,
            'serviceCode' => $serviceCode,
        ]);

        // Fetch the labels and shipping info
        return $shipment->getLabels($rate);
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return null;
    }

    public function getIsInternational(Order $order): bool
    {
        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        $sourceCountry = $storeLocation->countryCode ?? '';
        $destinationCountry = $order->shippingAddress->countryCode ?? '';

        return $storeLocation !== $destinationCountry;
    }

    public function getApiTypeOptions(): array
    {
        return [
            ['label' => Craft::t('postie', 'Rates Only'), 'value' => self::API_RATES],
            ['label' => Craft::t('postie', 'All'), 'value' => self::API_SHIPPING],
        ];
    }

    public function getBoxSizesSettings(): array
    {
        return [
            'name' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Name'),
            ],
            'boxLength' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Length'),
                'placeholder' => Craft::t('postie', '{unit}', ['unit' => static::getDimensionUnit()]),
                'thin' => true,
            ],
            'boxWidth' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Width'),
                'placeholder' => Craft::t('postie', '{unit}', ['unit' => static::getDimensionUnit()]),
                'thin' => true,
            ],
            'boxHeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Height'),
                'placeholder' => Craft::t('postie', '{unit}', ['unit' => static::getDimensionUnit()]),
                'thin' => true,
            ],
            'boxWeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Box Weight'),
                'placeholder' => Craft::t('postie', '{unit}', ['unit' => static::getWeightUnit()]),
                'thin' => true,
            ],
            'maxWeight' => [
                'type' => 'singleline',
                'heading' => Craft::t('postie', 'Max Weight'),
                'placeholder' => Craft::t('postie', '{unit}', ['unit' => static::getWeightUnit()]),
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

    public function getBoxSizesRows(): array
    {
        $boxSizes = [];

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

        unset($defaultBox);

        return array_merge($defaultBoxes, $this->boxSizes);
    }

    public function getTestRates(array $payload): RateResponse
    {
        // Set the Shippy logger for consolidated logging with Postie
        if ($logTarget = (Craft::$app->getLog()->targets['postie'] ?? null)) {
            Shippy::setLogger($logTarget->getLogger());
        }

        $order = new Order([
            'currency' => 'USD',
            'email' => 'test@test.com',
        ]);

        $from = new Address(array_merge([
            'firstName' => 'Testing',
            'lastName' => 'Sender',
            'organization' => 'Test Company',
        ], $payload['from']));

        $to = new Address(array_merge([
            'firstName' => 'Testing',
            'lastName' => 'Recipient',
            'organization' => 'Test Company',
        ], $payload['to']));

        // Create a Shippy shipment first for the origin/destination
        $shipment = new Shipment([
            'currency' => $order->currency,
            'from' => ShippyHelper::toAddress($order, $from),
            'to' => ShippyHelper::toAddress($order, $to),
        ]);

        // Add all the carriers we want to fetch rates for
        $shipment->addCarrier($this->getCarrier());

        $shipment->addPackage(new Package([
            'length' => $payload['length'],
            'width' => $payload['width'],
            'height' => $payload['height'],
            'weight' => $payload['weight'],
            'price' => '',
            'dimensionUnit' => $this->dimensionUnit,
            'weightUnit' => $this->weightUnit,
        ]));

        return $shipment->getRates();
    }

    public function beforeFetchRates(RateEvent $event): void
    {
        $fetchRatesEvent = new FetchRatesEvent([
            'request' => $event->getRequest(),
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_FETCH_RATES)) {
            $this->trigger(self::EVENT_BEFORE_FETCH_RATES, $fetchRatesEvent);
        }

        $event->setRequest($fetchRatesEvent->request);
    }

    public function afterFetchRates(RateEvent $event): void
    {
        $fetchRatesEvent = new FetchRatesEvent([
            'request' => $event->getRequest(),
            'response' => $event->getData(),
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_FETCH_RATES)) {
            $this->trigger(self::EVENT_AFTER_FETCH_RATES, $fetchRatesEvent);
        }

        $event->setRequest($fetchRatesEvent->request);
        $event->setData($fetchRatesEvent->response);
    }


    // Protected Methods
    // =========================================================================

    protected function getLineItemDimensions(LineItem $lineItem): array|bool
    {
        $settings = Commerce::getInstance()->getSettings();

        // We always deal with g/mm for box-packing, which is suitable for int's
        $weight = (new Mass($lineItem->weight, $settings->weightUnits))->toUnit('g');
        $length = (new Length($lineItem->length, $settings->dimensionUnits))->toUnit('mm');
        $width = (new Length($lineItem->width, $settings->dimensionUnits))->toUnit('mm');
        $height = (new Length($lineItem->height, $settings->dimensionUnits))->toUnit('mm');

        $dimensions = [
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'weight' => $weight,
        ];

        // Check if this line item actually has dimensions or weight
        if (!array_filter($dimensions)) {
            return false;
        }

        return $dimensions;
    }

    protected function getOrderDimensions(Order $order, string $weightUnit, string $dimensionUnit): array
    {
        $settings = Commerce::getInstance()->getSettings();

        $maxWidth = 0;
        $maxLength = 0;
        $totalHeight = 0;

        // Get box package dimensions based on order line items
        foreach (PostieHelper::getOrderLineItems($order) as $key => $lineItem) {
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
            'width' => $width,
            'height' => $height,
            'weight' => $weight,
        ];
    }

    protected function getBoxItemFromLineItem(LineItem $lineItem): bool|Item
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

    protected function getBoxFromLineItem(LineItem $lineItem): bool|Box
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

    protected function getBoxSizes(): array
    {
        $boxes = [];

        // Because our box-packer only deals with integers, we should convert whatever unit this provider is using
        // (which is the same as what unit boxes are created in) and convert to g/mm to resolve as int's effectively.
        foreach ($this->getBoxSizesRows() as $i => $boxInfo) {
            if (!(bool)$boxInfo['enabled']) {
                continue;
            }

            $boxInfo['maxWeight'] = (new Mass($boxInfo['maxWeight'], static::getWeightUnit()))->toUnit('g');
            $boxInfo['boxWeight'] = (new Mass($boxInfo['boxWeight'], static::getWeightUnit()))->toUnit('g');
            $boxInfo['boxLength'] = (new Length($boxInfo['boxLength'], static::getDimensionUnit()))->toUnit('mm');
            $boxInfo['boxWidth'] = (new Length($boxInfo['boxWidth'], static::getDimensionUnit()))->toUnit('mm');
            $boxInfo['boxHeight'] = (new Length($boxInfo['boxHeight'], static::getDimensionUnit()))->toUnit('mm');

            $boxes[] = $boxInfo;
        }

        return $boxes;
    }

    public function packOrder(Order $order)
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
            $dimensions = $this->getOrderDimensions($order, static::getWeightUnit(), static::getDimensionUnit());

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

            foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
                if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                    $packer->addItem($boxItem, $lineItem->qty);
                }
            }
        }

        // If packing boxes individually, create boxes exactly the same size as each item
        if ($this->packingMethod === self::PACKING_PER_ITEM) {
            foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
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
            foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
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

        $packedBoxes = new PackedBoxes($packedBoxes, static::getWeightUnit(), static::getDimensionUnit());

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

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('US', ['locality' => 'Cupertino']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('US', ['locality' => 'Mountain View']);
    }

    public function getTestingPackage(): array
    {
        return TestingHelper::getTestPackedBoxes(static::getDimensionUnit(), static::getWeightUnit())[0];
    }
}
