# Events

Events can be used to extend the functionality of Postie.

## Rates related events

### The `beforeFetchRates` event

Plugins can get notified before rates are fetched from a provider. 

```php
use verbb\postie\base\Provider;
use verbb\postie\events\FetchRatesEvent;
use verbb\postie\providers\USPS;
use yii\base\Event;

Event::on(USPS::class, Provider::EVENT_BEFORE_FETCH_RATES, function(FetchRatesEvent $event) {
    $event->storeLocation = [];
    $event->dimensions = [];
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
