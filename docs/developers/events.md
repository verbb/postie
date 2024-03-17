# Events
Events can be used to extend the functionality of Postie.

## Rates related events

### The `beforeFetchRates` event
The event raised before the rates are fetched. Primarily used to modify the package dimensions, or store information.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_BEFORE_FETCH_RATES, function(FetchRatesEvent $event) {
    $storeLocation = $event->storeLocation;
    $packedBoxes = $event->packedBoxes;

    // For example, updating dimensions/weight of boxes, items packed into boxes, and more
    foreach ($event->packedBoxes->packedBoxList as $packedBox) {
        // Update the internal reference (name) for the box
        $packedBox->getBox()->setReference('Some special box');

        // Update the maximum weight
        $packedBox->getBox()->setMaxWeight(9999);

        // Modify dimensions/weights of items in a box
        foreach ($packedBox->getItems() as $packedItem) {
            $packedItem->getItem()->setWidth(12);
            $packedItem->getItem()->setHeight(5);
        }
    }
});
```

### The `modifyPayload` event
The event raised before the payload is sent to a provider. You can modify the payload to suit your needs before being sent.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyPayloadEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_MODIFY_PAYLOAD, function(ModifyPayloadEvent $event) {
    $provider = $event->provider;
    $payload = $event->payload;
    $order = $event->order;

    // To modify the payload, directly modify the variable via `$event->payload = ...`
});
```

### The `modifyRates` event
Plugins can get notified when rates are fetched from a provider. You can modify these rates, or access anything in the response from the provider. Be sure to modify the `rates` property.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_MODIFY_RATES, function(ModifyRatesEvent $event) {
    $rates = $event->rates; // The calculated rates from Postie
    $response = $event->response; // The raw response from the provider's API

    // To modify the rates, directly modify the variable via `$event->rates = ...`

});
```

### The `beforePackOrder` event
Plugins can get notified before the order contents are packed in boxes.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\PackOrderEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_BEFORE_PACK_ORDER, function(PackOrderEvent $event) {
    $order = $event->order;
    $packer = $event->packer;
});
```

It is also possible to override `packer` with your own class that extends `DVDoug\BoxPacker\Packer`.

```
Event::on(USPS::class, Provider::EVENT_BEFORE_PACK_ORDER, function (PackOrderEvent $event) {
    // Use your own custom packer class
    $event->packer = new CustomPacker();
});
```


### The `afterPackOrder` event
Plugins can get notified after the order contents are packed in boxes.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\PackOrderEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_AFTER_PACK_ORDER, function(PackOrderEvent $event) {
    $order = $event->order;
    $packedBoxes = $event->packedBoxes;
    $packer = $event->packer;
});
```

### The `modifyShippingRule` event
Plugins can modify anything about a shipping rule for a shipping method. This includes the description, price and more.

```php
use verbb\postie\events\ModifyShippingRuleEvent;
use verbb\postie\models\ShippingMethod;
use yii\base\Event;

Event::on(ShippingMethod::class, ShippingMethod::EVENT_MODIFY_SHIPPING_RULE, function(ModifyShippingRuleEvent $event) {
    // To modify the rule, directly modify the variable via `$event->shippingRule = ...`
});
```

### The `modifyShippingMethods` event
Plugins can modify the returned shipping methods, after they have been created from the provider.

```php
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_MODIFY_SHIPPING_METHODS, function(ModifyShippingMethodsEvent $event) {
    $provider = $event->provider;
    $shippingMethods = $event->shippingMethods;
    $order = $event->order;

    // To modify the shipping methods, directly modify the variable via `$event->shippingMethods = ...`
});
```

### The `beforeRegisterShippingMethods` event
Plugins can modify the returned shipping methods, after they have been fetched from all providers. This differs from the `modifyShippingMethods` event as it is not per-provider.

```php
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\services\Service;
use yii\base\Event;

Event::on(Service::class, Service::EVENT_BEFORE_REGISTER_SHIPPING_METHODS, function(ModifyShippingMethodsEvent $event) {
    $shippingMethods = $event->shippingMethods;
    $order = $event->order;

    // To modify the shipping methods, directly modify the variable via `$event->shippingMethods = ...`
});
```

### The `modifyVariantQuery` event
Plugins can modify the Variant query for Postie's settings. Postie will by default look at all variants across your site and report on whether weight and dimension values are set. You can modify this query to suit your needs.

```php
use verbb\postie\controllers\PluginController;
use verbb\postie\events\ModifyShippableVariantsEvent;
use yii\base\Event;

Event::on(PluginController::class, PluginController::EVENT_MODIFY_VARIANT_QUERY, function(ModifyShippableVariantsEvent $event) {
    // To modify the query, directly modify the variable via `$event->query = ...`
});
```
