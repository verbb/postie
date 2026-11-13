# Events

Postie provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Postie’s behaviour.

## Plugin Events

### The `modifyVariantQuery` Event
The event that is triggered before Postie checks variants for missing weight and dimension values. By default, Postie looks at every variant across your site and reports which ones are missing these values. Set `$event->query` to a different variant query to change which variants are checked.

```php
use verbb\postie\events\ModifyShippableVariantsEvent;
use verbb\postie\Postie;
use yii\base\Event;

Event::on(Postie::class, Postie::EVENT_MODIFY_VARIANT_QUERY, function(ModifyShippableVariantsEvent $event) {
    // Only check variants for a specific product type
    $event->query->typeId(1);
});
```

## Provider Events

### The `registerProviderTypes` Event
The event that is triggered when registering provider types. Add your own provider class to `$event->providerTypes` to make it available alongside the built-in providers. See [Custom Providers](custom-providers.md) for a complete example.

```php
use modules\sitemodule\MyProvider;
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterProviderTypesEvent $event) {
    $event->providerTypes[] = MyProvider::class;
});
```

### The `beforeSaveProvider` Event
The event that is triggered before a provider is saved.

The event can't cancel the save. Changes you make to `$event->provider` are saved.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_BEFORE_SAVE_PROVIDER, function(ProviderEvent $event) {
    $provider = $event->provider;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveProvider` Event
The event that is triggered after a provider is saved.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_AFTER_SAVE_PROVIDER, function(ProviderEvent $event) {
    $provider = $event->provider;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteProvider` Event
The event that is triggered before a provider is deleted.

The event can't cancel the deletion.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_BEFORE_DELETE_PROVIDER, function(ProviderEvent $event) {
    $provider = $event->provider;
    // ...
});
```

### The `beforeApplyProviderDelete` Event
The event that is triggered before a provider delete is applied to the database, when Postie processes the project config change. This runs on every environment that applies the change, including deployments that sync project config.

The event can't cancel the deletion.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_BEFORE_APPLY_PROVIDER_DELETE, function(ProviderEvent $event) {
    $provider = $event->provider;
    // ...
});
```

### The `afterDeleteProvider` Event
The event that is triggered after a provider is deleted.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_AFTER_DELETE_PROVIDER, function(ProviderEvent $event) {
    $provider = $event->provider;
    // ...
});
```

## Rate and Label Fetching Events

These events are triggered on the provider instance. Use a specific provider class, such as `USPS`, to listen to one provider, or `verbb\postie\base\Provider` to listen to all of them.

### The `beforeFetchRates` Event
The event that is triggered before rates are fetched.

You can change the request sent to the provider’s API through `$event->request`. The event also gives you the `order` and the Shippy `carrier`.

```php
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_BEFORE_FETCH_RATES, function(FetchRatesEvent $event) {
    // This will return a `verbb\shippy\models\Request` instance
    $request = $event->request;

    // Fetch the HTTP client, method, endpoint and payload
    $httpClient = $request->getHttpClient();
    $method = $request->getMethod();
    $endpoint = $request->getEndpoint();
    $payload = $request->getPayload();

    // Modify the raw payload before it hits the provider API
    $payload['query']['from_postcode'] = '9999';
    $event->request->payload = $payload;

    // Modify the endpoint for the API call
    $event->request->endpoint = 'some/other/endpoint';
});
```

### The `afterFetchRates` Event
The event that is triggered after rates are fetched.

`$event->response` holds the response data from the provider. Use this event to read the response, as changing `$event->response` doesn’t change the rates Postie returns.

```php
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_AFTER_FETCH_RATES, function(FetchRatesEvent $event) {
    $order = $event->order;
    $response = $event->response;
});
```

### The `beforeFetchLabels` Event
The event that is triggered before shipping labels are fetched for a shipment.

You can change the request sent to the provider’s API through `$event->request`.

```php
use verbb\postie\events\FetchLabelsEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_BEFORE_FETCH_LABELS, function(FetchLabelsEvent $event) {
    $request = $event->request;
    $carrier = $event->carrier;
});
```

### The `afterFetchLabels` Event
The event that is triggered after shipping labels are fetched for a shipment.

`$event->response` holds the response data from the provider. Use this event to read the response, as changing `$event->response` doesn’t change the labels Postie returns.

```php
use verbb\postie\events\FetchLabelsEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_AFTER_FETCH_LABELS, function(FetchLabelsEvent $event) {
    $carrier = $event->carrier;
    $response = $event->response;
});
```

### The `beforePackOrder` Event
The event that is triggered before the order contents are packed in boxes.

You can replace `$event->packer` with your own class that extends `DVDoug\BoxPacker\Packer`, or replace `$event->order`.

```php
use verbb\postie\events\PackOrderEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_BEFORE_PACK_ORDER, function(PackOrderEvent $event) {
    $order = $event->order;

    // Use your own custom packer class
    $event->packer = new CustomPacker();
});
```

### The `afterPackOrder` Event
The event that is triggered after the order contents are packed in boxes.

Changes you make to `$event->packedBoxes` are used as the packed boxes for the order.

```php
use verbb\postie\events\PackOrderEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_AFTER_PACK_ORDER, function(PackOrderEvent $event) {
    $order = $event->order;
    $packedBoxes = $event->packedBoxes;
    $packer = $event->packer;
});
```

## Shipping Method Events

### The `modifyRateFingerprint` Event
The event that is triggered before Postie creates the private fingerprint used to decide whether a cached live quote still matches an order. Postie includes its built-in order, address, line-item, store and unit inputs automatically, together with one-way digests of the enabled provider configurations. If custom rate or packing logic depends on additional data, add that data here so a change causes a fresh quote.

Do not add secrets as array keys or log the fingerprint data. Postie hashes the complete data structure before using it for cache matching.

```php
use Craft;
use verbb\postie\events\ModifyRateFingerprintEvent;
use verbb\postie\services\Service;
use yii\base\Event;

Event::on(Service::class, Service::EVENT_MODIFY_RATE_FINGERPRINT, function(ModifyRateFingerprintEvent $event) {
    $event->fingerprintData['warehouseZone'] = Craft::$app->getConfig()->getCustom()->warehouseZone;
});
```

### The `beforeRegisterShippingMethods` Event
The event that is triggered when rates are converted to shipping methods.

Changes you make to `$event->shippingMethods` change the shipping methods offered for the order.

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

### The `modifyShippingRule` Event
The event that is triggered to modify a shipping rule for a shipping method. This includes the description, price and more.

```php
use verbb\postie\events\ModifyShippingRuleEvent;
use verbb\postie\models\ShippingMethod;
use yii\base\Event;

Event::on(ShippingMethod::class, ShippingMethod::EVENT_MODIFY_SHIPPING_RULE, function(ModifyShippingRuleEvent $event) {
    // To modify the rule, directly modify the variable via `$event->shippingRule = ...`
});
```

## Rate Events

Postie saves the rate a customer selected when their order is completed, so it can be used to lodge shipments later.

### The `beforeSaveRate` Event
The event that is triggered before a rate is saved.

The event can't cancel the save. Changes you make to `$event->rate` are saved.

```php
use verbb\postie\events\RateEvent;
use verbb\postie\services\Rates;
use yii\base\Event;

Event::on(Rates::class, Rates::EVENT_BEFORE_SAVE_RATE, function(RateEvent $event) {
    $rate = $event->rate;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveRate` Event
The event that is triggered after a rate is saved.

```php
use verbb\postie\events\RateEvent;
use verbb\postie\services\Rates;
use yii\base\Event;

Event::on(Rates::class, Rates::EVENT_AFTER_SAVE_RATE, function(RateEvent $event) {
    $rate = $event->rate;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteRate` Event
The event that is triggered before a rate is deleted.

The event can't cancel the deletion.

```php
use verbb\postie\events\RateEvent;
use verbb\postie\services\Rates;
use yii\base\Event;

Event::on(Rates::class, Rates::EVENT_BEFORE_DELETE_RATE, function(RateEvent $event) {
    $rate = $event->rate;
    // ...
});
```

### The `afterDeleteRate` Event
The event that is triggered after a rate is deleted.

```php
use verbb\postie\events\RateEvent;
use verbb\postie\services\Rates;
use yii\base\Event;

Event::on(Rates::class, Rates::EVENT_AFTER_DELETE_RATE, function(RateEvent $event) {
    $rate = $event->rate;
    // ...
});
```

## Shipment Events

### The `beforeSaveShipment` Event
The event that is triggered before a shipment is saved, including when a shipment is lodged with a provider.

The event can't cancel the save. Changes you make to `$event->shipment` are saved.

```php
use verbb\postie\events\ShipmentEvent;
use verbb\postie\services\Shipments;
use yii\base\Event;

Event::on(Shipments::class, Shipments::EVENT_BEFORE_SAVE_SHIPMENT, function(ShipmentEvent $event) {
    $shipment = $event->shipment;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveShipment` Event
The event that is triggered after a shipment is saved.

```php
use verbb\postie\events\ShipmentEvent;
use verbb\postie\services\Shipments;
use yii\base\Event;

Event::on(Shipments::class, Shipments::EVENT_AFTER_SAVE_SHIPMENT, function(ShipmentEvent $event) {
    $shipment = $event->shipment;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteShipment` Event
The event that is triggered before a shipment is deleted.

The event can't cancel the deletion.

```php
use verbb\postie\events\ShipmentEvent;
use verbb\postie\services\Shipments;
use yii\base\Event;

Event::on(Shipments::class, Shipments::EVENT_BEFORE_DELETE_SHIPMENT, function(ShipmentEvent $event) {
    $shipment = $event->shipment;
    // ...
});
```

### The `afterDeleteShipment` Event
The event that is triggered after a shipment is deleted.

```php
use verbb\postie\events\ShipmentEvent;
use verbb\postie\services\Shipments;
use yii\base\Event;

Event::on(Shipments::class, Shipments::EVENT_AFTER_DELETE_SHIPMENT, function(ShipmentEvent $event) {
    $shipment = $event->shipment;
    // ...
});
```
