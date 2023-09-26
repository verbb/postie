# Events
Postie provides a collection of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Postie’s behavior.

## Plugin Events

### The `modifyVariantQuery` event
Postie will by default look at all variants across your site and report on whether weight and dimension values are set. You can modify this query to suit your needs.

```php
use verbb\postie\postie\Postie;
use verbb\postie\events\ModifyShippableVariantsEvent;
use yii\base\Event;

Event::on(Postie::class, Postie::EVENT_MODIFY_VARIANT_QUERY, function(ModifyShippableVariantsEvent $event) {
    // To modify the query, directly modify the variable via `$event->query = ...`
});
```

## Provider Events

### The `beforeFetchRates` event
The event that is triggered before rates are fetched.

```php
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

use verbb\shippy\models\Request;

Event::on(USPS::class, USPS::EVENT_BEFORE_FETCH_RATES, function(FetchRatesEvent $event) {
    // This will return a `verbb\shippy\models\Request` instance
    $request = $event->request;

    // Fetch the HTTP client, method, enspoint and payload
    $httpClient = $request->getHttpClient();
    $method = $request->getMethod();
    $endpoint = $request->getEndpoint();
    $payload = $request->getPayload();

    // Modify the raw payload before it hits the providers API
    $payload['query']['from_postcode'] = '9999';
    $event->request->payload = $payload;

    // Modify the endpoint for the API call
    $event->request->endpoint = 'some/other/endpoint';
});
```

### The `afterFetchRates` event
The event that is triggered after rates are fetched.

```php
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

use verbb\shippy\models\Request;

Event::on(USPS::class, USPS::EVENT_AFTER_FETCH_RATES, function(FetchRatesEvent $event) {
    $request = $event->request;
    $response = $event->response;
});
```

### The `beforePackOrder` event
The event that is triggered before the order contents are packed in boxes.

```php
use verbb\postie\events\PackOrderEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, USPS::EVENT_BEFORE_PACK_ORDER, function(PackOrderEvent $event) {
    $order = $event->order;
    $packer = $event->packer;
});
```

### The `afterPackOrder` event
The event that is triggered after the order contents are packed in boxes.

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

### The `beforeSaveProvider` event
The event that is triggered before a provider is saved.

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

### The `afterSaveProvider` event
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

### The `beforeDeleteProvider` event
The event that is triggered before a provider is deleted.

```php
use verbb\postie\events\ProviderEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_BEFORE_DELETE_PROVIDER, function(ProviderEvent $event) {
    $provider = $event->provider;
    // ...
});
```

### The `afterDeleteProvider` event
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

## Shipping Method Events

### The `beforeRegisterShippingMethods` event
The event that is triggered when rates are converted to shipping methods.

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
### The `modifyShippingRule` event
The event that is triggered to modify a shipping rule for a shipping method. This includes the description, price and more.

```php
use verbb\postie\events\ModifyShippingRuleEvent;
use verbb\postie\models\ShippingMethod;
use yii\base\Event;

Event::on(ShippingMethod::class, ShippingMethod::EVENT_MODIFY_SHIPPING_RULE, function(ModifyShippingRuleEvent $event) {
    // To modify the rule, directly modify the variable via `$event->shippingRule = ...`
});
```
