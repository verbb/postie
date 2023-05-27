# Provider
You can register additional shipping providers to post to by registering your class. Then, you'll need to create your provider class to implement the `ProviderInterface`.

:::tip
If you're not confident in PHP development, but still looking for provider support, [contact us](/contact), and we'd love to add your provider to Postie.
:::

## The `registerProviderTypes` event

```php
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterProviderTypesEvent $event) {
    $event->providerTypes[] = MyProvider::class;
});
```

## Provider Class

```php
<?php
namespace myplugin\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

class MyProvider extends Provider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'g';
    public $dimensionUnit = 'cm';


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'My Provider');
    }

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('plugin-path/provider', ['provider' => $this]);
    }

    public function getIconUrl()
    {   
        // Replace with your path, or remove entirely for no icon
        return Craft::$app->assetManager->getPublishedUrl('@namespace/plugin/resources/dist/img/provider-icon.svg', true);
    }

    public function getServiceList(): array
    {
        return [
            'SERVICE_HANDLE' => 'Service Name',
            // ...
        ];
    }

    public function fetchShippingRates($order)
    {
        // Setup some local, runtime-level caching
        if ($this->_rates) {
            return $this->_rates;
        }

        // Fetch the origin location - to be used to determine where to ship _from_
        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        try {

            //
            // Implement your fetching rates from the API
            //

            // Return an array of values, keyed by each available service handle
            $this->_rates['SERVICE_HANDLE'] = [
                'amount' => 10.0
            ];
            // ...

        } catch (\Throwable $e) {
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->_rates;
    }
}
```

## Properties and Methods

### public static function displayName()
This defines the name of your shipping provider.

### public function getSettingsHtml()
Here you need to define the path and filename to a template file which contains further settings description. If you don't want to provide a description then just create an empty file. You have to place it within the `template` folder of your plugin. This file is where you'll place fields to enter in API settings.

### public function getServiceList()
This function should return an array of the provided services as a key-value pair.

- `key` - defines the handle of the service. Should be unique. Best practice is to prepend your plugin handle seperated by an underline.

- `value` - defines the name of the service. It should be self explaining to avoid confusion on customer side.

```php
return [
    'MYSHIPPINGPROVIDER_DOMESTIC'      => 'Domestic',
    'MYSHIPPINGPROVIDER_INTERNATIONAL' => 'International',
];
```

### public function fetchShippingRates($order)
The `fetchShippingRates` method is the main function in your class. It's where all the _magic_ happens.

#### Parameters
- `order` - this parameter contains the _Order_ with all relevant order details as address, line items, total dimensions and so on. You will need this information to create the API call.

#### Return
The `fetchShippingRates` method should return an array of rates, each keyed by their service handle. For example:

```php
[
    'MYSHIPPINGPROVIDER_DOMESTIC' => '10.2',
    'MYSHIPPINGPROVIDER_INTERNATIONAL' => '55.23',
]
```

## Logging
For error catching we scaffolded a try-catch block. Caught errors will be written in the Postie log file. If you want to write errors into your own log file simply replace this log call with your own plugin name.

