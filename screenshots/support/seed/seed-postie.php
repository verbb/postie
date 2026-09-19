/** Seed current Postie provider, product, order, rate and shipment records. */

use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\ProductType;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\StoreSettings as StoreSettingsRecord;
use craft\elements\Address;
use craft\helpers\Json;
use craft\models\FieldLayout;
use verbb\postie\Postie;
use verbb\postie\models\Rate;
use verbb\postie\models\Shipment;
use verbb\postie\providers\AustraliaPost;
use verbb\postie\providers\DHLExpress;
use verbb\postie\providers\FedEx;
use verbb\postie\providers\UPS;
use verbb\postie\records\Shipment as ShipmentRecord;

$commerce = Commerce::getInstance();
$elements = Craft::$app->getElements();
$site = Craft::$app->getSites()->getPrimarySite();
$store = $commerce->getStores()->getPrimaryStore();

$storeAddress = $store->getSettings()->getLocationAddress();
$storeAddress->title = 'Verbb Warehouse';
$storeAddress->organization = 'Verbb & Co.';
$storeAddress->addressLine1 = '18 Exhibition Street';
$storeAddress->locality = 'Melbourne';
$storeAddress->administrativeArea = 'VIC';
$storeAddress->postalCode = '3000';
$storeAddress->countryCode = 'AU';
$elements->saveElement($storeAddress, false);
StoreSettingsRecord::updateAll(['locationAddressId' => $storeAddress->id], ['id' => $store->getSettings()->id]);
$store->getSettings()->setLocationAddress($storeAddress);

$providers = Postie::$plugin->getProviders();
$providerConfigs = [
    [AustraliaPost::class, 'Australia Post', 'australiaPost', ['apiKey' => 'screenshot']],
    [DHLExpress::class, 'DHL Express', 'dhlExpress', ['accountNumber' => '123456789', 'username' => 'screenshot', 'password' => 'screenshot']],
    [FedEx::class, 'FedEx', 'fedEx', ['clientId' => 'screenshot', 'clientSecret' => 'screenshot', 'accountNumber' => '123456789']],
    [UPS::class, 'UPS', 'ups', ['clientId' => 'screenshot', 'clientSecret' => 'screenshot', 'accountNumber' => '123456789']],
];

foreach ($providerConfigs as [$type, $name, $handle, $settings]) {
    if ($providers->getProviderByHandle($handle)) {
        continue;
    }

    $provider = $providers->createProvider([
        'type' => $type,
        'name' => $name,
        'handle' => $handle,
        'enabled' => true,
        'isProduction' => false,
        'markUpRate' => $handle === 'fedEx' ? 8 : null,
        'markUpBase' => 'percentage',
        'packingMethod' => $handle === 'fedEx' ? 'boxPacking' : 'perItem',
        'boxSizes' => $handle === 'fedEx' ? array_map(static function(array $box): array {
            $box['enabled'] = true;
            $box['default'] = true;
            return $box;
        }, FedEx::defineDefaultBoxes()) : [],
        'settings' => $settings,
    ]);

    if (!$providers->saveProvider($provider, false)) {
        throw new RuntimeException('Unable to save Postie screenshot provider: ' . $handle);
    }
}

$fedEx = $providers->getProviderByHandle('fedEx');
$australiaPost = $providers->getProviderByHandle('australiaPost');

if (!$fedEx || !$australiaPost) {
    throw new RuntimeException('Unable to resolve Postie screenshot providers.');
}

$productTypes = $commerce->getProductTypes();
$productType = $productTypes->getProductTypeByHandle('postieScreenshotGoods');

if (!$productType) {
    $productType = new ProductType([
        'name' => 'Homewares',
        'handle' => 'postieScreenshotGoods',
        'hasDimensions' => true,
        'hasProductTitleField' => true,
        'hasVariantTitleField' => true,
        'maxVariants' => 12,
    ]);
    $productType->getBehavior('productFieldLayout')->setFieldLayout(new FieldLayout(['type' => Product::class]));
    $productType->getBehavior('variantFieldLayout')->setFieldLayout(new FieldLayout(['type' => Variant::class]));
    $productType->setSiteSettings([
        $site->id => new ProductTypeSite([
            'siteId' => $site->id,
            'hasUrls' => false,
            'enabledByDefault' => true,
        ]),
    ]);

    if (!$productTypes->saveProductType($productType)) {
        throw new RuntimeException('Unable to save Postie screenshot product type: ' . Json::encode($productType->getErrors()));
    }
}

if (!$productType->hasDimensions) {
    $productType->hasDimensions = true;

    if (!$productTypes->saveProductType($productType)) {
        throw new RuntimeException('Unable to enable dimensions for the Postie screenshot product type: ' . Json::encode($productType->getErrors()));
    }
}

$productData = [
    ['Harbour linen throw', 'POSTIE-THROW', 189, 2],
    ['Stoneware serving set', 'POSTIE-STONEWARE', 145, 1],
];
$variants = [];

foreach ($productData as [$title, $sku, $price, $qty]) {
    $product = Product::find()->typeId($productType->id)->title($title)->siteId($site->id)->status(null)->one();

    if (!$product) {
        $product = new Product([
            'typeId' => $productType->id,
            'siteId' => $site->id,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'enabled' => true,
        ]);
        $elements->saveElement($product, false);
    }

    $variant = Variant::find()->sku($sku)->status(null)->one();

    if (!$variant) {
        $variant = new Variant([
            'title' => $title,
            'siteId' => $site->id,
            'enabled' => true,
            'availableForPurchase' => true,
            'inventoryTracked' => false,
            'isDefault' => true,
            'weight' => $sku === 'POSTIE-THROW' ? 900 : 2400,
            'length' => $sku === 'POSTIE-THROW' ? 0 : 32,
            'width' => $sku === 'POSTIE-THROW' ? 0 : 24,
            'height' => $sku === 'POSTIE-THROW' ? 0 : 18,
        ]);
        $variant->setSku($sku);
        $variant->setBasePrice($price);
        $variant->setOwnerId($product->id);
        $variant->setPrimaryOwnerId($product->id);
        $elements->saveElement($variant, false);
        $product->setVariants([$variant]);
        $elements->saveElement($product, false);
    }

    $variant->weight = $sku === 'POSTIE-THROW' ? 900 : 2400;
    $variant->length = $sku === 'POSTIE-THROW' ? 0 : 32;
    $variant->width = $sku === 'POSTIE-THROW' ? 0 : 24;
    $variant->height = $sku === 'POSTIE-THROW' ? 0 : 18;
    $elements->saveElement($variant, false);

    $variants[] = [$variant, $qty];
}

$order = Order::find()->number('POSTIE-SCREENSHOT-1048')->status(null)->one();

if (!$order) {
    $order = new Order([
        'number' => 'POSTIE-SCREENSHOT-1048',
        'origin' => Order::ORIGIN_CP,
        'storeId' => $store->id,
        'currency' => $store->getCurrency(),
        'email' => 'amelia.hart@example.com',
    ]);

    foreach ($variants as [$variant, $qty]) {
        $lineItem = $commerce->getLineItems()->create($order, [
            'purchasableId' => $variant->id,
            'qty' => $qty,
        ]);
        $order->addLineItem($lineItem);
    }

    if (!$elements->saveElement($order, false)) {
        throw new RuntimeException('Unable to save Postie screenshot order: ' . Json::encode($order->getErrors()));
    }
}

$lineItems = $order->getLineItems();
$rate = Postie::$plugin->getRates()->getRatesByOrderId($order->id)[0] ?? null;

if (!$rate) {
    $rate = new Rate([
        'orderId' => $order->id,
        'providerHandle' => $australiaPost->handle,
        'rate' => '18.40',
        'service' => 'AUS_PARCEL_EXPRESS',
        'response' => ['name' => 'Express Post', 'currency' => 'AUD'],
    ]);
    Postie::$plugin->getRates()->saveRate($rate, false);
}

$shipment = Postie::$plugin->getShipments()->getShipmentsByOrderId($order->id)[0] ?? null;

if (!$shipment && $lineItems) {
    $shipment = new Shipment([
        'orderId' => $order->id,
        'providerHandle' => $australiaPost->handle,
        'trackingNumber' => '33XH2R10482701000093501',
        'lineItems' => [$lineItems[0]->id => 1],
        'labels' => ['id' => 'AP-1048', 'mime' => 'application/pdf', 'data' => base64_encode('Postie screenshot label')],
        'response' => ['status' => 'lodged'],
    ]);
    Postie::$plugin->getShipments()->saveShipment($shipment, false);
}

if ($shipment?->id) {
    ShipmentRecord::updateAll([
        'dateCreated' => '2025-08-14 10:30:00',
        'dateUpdated' => '2025-08-14 10:30:00',
    ], ['id' => $shipment->id]);
}

echo Json::encode([
    'providerRoute' => '/admin/postie/providers/edit/' . $fedEx->id,
    'providersRoute' => '/admin/postie/providers',
    'storeSetupRoute' => '/admin/postie/store-setup',
    'orderRoute' => '/admin/commerce/orders/' . $order->id . '#shipmentsTab',
], JSON_THROW_ON_ERROR);
