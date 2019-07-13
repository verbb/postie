# Events

Events can be used to extend the functionality of Postie.

## Rates related events

### The `modifyRates` event

Plugins can get notified when rates are fetched from a provider. You can modify these rates, or access anything in the response from the provider. Be sure to modify the `rules`

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
