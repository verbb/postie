# Shipping Provider

Postie allows you to create your own shipping provider classes, which is useful in creating supported providers for other postage carriers.

:::tip
If you're not confident in PHP development, but still looking for provider support, [contact us](/contact) and we'd love to add your provider to Postie.
:::

To create your shipping provider with Postie, start by creating a new plugin, and adding a new class to contain your provider implementation.

```php
namespace Craft;

class MyShippingProvider extends \Postie\Providers\BaseProvider
{
    public static $handle = 'myShippingProvider';

    public function __construct()
    {
        parent::__construct();

        $this->_handle = self::$handle;

        $settings = $this->getProviderSettings(self::$handle);
        $this->_name = $settings['name'];
        $this->_services = $settings['services'];
    }
    
    public function getAPIFields()
    {
        return [
            'apiKey',
            'apiPassword',
        ];
    }

    public function getSettingsTemplate()
    {
        return 'myshippingprovider/template.html';
    }
    
    public function getServiceList()
    {
        return [
            'MYSHIPPINGPROVIDER_DOMESTIC'      => 'Domestic',
            'MYSHIPPINGPROVIDER_INTERNATIONAL' => 'International',
        ];
    }
    
    public function createShipping($handle, \Craft\Commerce_OrderModel $order)
    {
        try {           
            // API calls getting $basePrice
            
            return (float)$basePrice;
            
        } catch (\ErrorException $e) {

            $msg = str_replace(PHP_EOL, ' ', $e->getMessage());
            PostiePlugin::log($msg, \Craft\LogLevel::Error, true);
        }

        return 0.00;
    }
}
```

## Properties and Methods

### public static $handle

This defines the handle of your shipping provider. This handle should be unique.

### public function construct()

This function sets your settings from the database or the config entry for your provider. The basic settings you have to set are

- `handle` - the handle of your shipping provider, should be unique
- `name` - a simple name of your shipping provider
- `services` - a list of your enabled services

You can always set more attributes in your configuration, for instance `apiKey` or `username`. This attributes are available via the `$this->getProviderSettings(self::$handle);` function.

### public function getAPIFields()

This function should return an array of API fields needed for the API call of your provider. For instance `key` or `password`. These will vary depending on your provider.

### public function getSettingsTemplate()

Here you need to define the path and filename to a template file which contains further settings description. If you don't want to provide a description then just create an empty file. You have to place it within the `template` folder of your plugin.

#### Localisation

If you want to translate your template file just add a `translations` folder into your plugin and place a file with the language locale you want to translate in it.

![My Shipping Provider Template](/docs/screenshots/my-shipping-provider-template.png)

For more details see Craft's documentation about [translating static text](https://craftcms.com/support/static-translations).

### public function getServiceList()

This function should return an array of the provided services as a key-value pair.

- `key` - defines the handle of the service. Should be unique. Best practice is to prepend your plugin handle seperated by a underline.

- `value` - defines the name of the service. It should be self explaining to avoid confusion on customer side.

```php
return [
    'MYSHIPPINGPROVIDER_DOMESTIC'      => 'Domestic',
    'MYSHIPPINGPROVIDER_INTERNATIONAL' => 'International',
];
```

### public function createShipping($serviceHandle, $order)

The `createShipping` method is the main function in your class. Its where all the _magic_ happens.

#### Parameters

This function needs two parameters:

- `serviceHandle` - this is the `key` of the service you defined in he _getServiceList_ function. The _BaseProvider_ class loops trough every service and calls the `createShipping` method with your specified service handle.
- `order` - this parameter contains the _Commerce\_OrderModel_ with all relevant order details as address, line items, total dimensions and so on. You will need this information to create the API call.

#### Return

The `createShipping` method should return the calculated base price as a float value. If no price is determined it returns a default value of `0.00`. The _BaseProvider_ class check if a value greater then 0 was returned. If not, the service is disabled and will not displayed for the customer.

## Logging

For error catching we scaffolded a try-catch block. Caught errors will be written in the Postie log file. If you want to write errors into your own log file simply replace this log call with your own plugin name.

## Calculate Dimensions

Unfortunately every provider uses a different measurement system. European shipping provider mostly calculate in the metric system while North American providers mostly uses the imperial system. This is why we have to check for the Craft Commerce dimension and weight unit settings, recalculate the dimensions into the necessary measurement system and create the API call.

The best practice is to separate the dimension calculation into a private method, for instance:

```php
private function _getDimensions($order)
```

In the new method we get the commerce settings via the commerce `getSettings()` method:

```php
craft()->commerce_settings->getSettings()
```

We need to check for two settings:

- `weightUnits`

- Pounds `lb`
- Gram`g`
- Kilogram `kg`
- `dimensionUnits`

- Inches `in`
- Feet `ft`
- Millimeters `mm`
- Centimeters `cm`
- Meters `m`

Based of which measurement system your provider is using you have to convert into the relevant unit. Check the **`AustraliaPostProvider`** or **`USPSProvider`** class to get an idea how to handle this.

## Using Postie's Box Packing Algorithm

Postie uses a simple [box packing algorithm](docs:support/faqs) to get box dimensions based of the line item dimensions. To use this algorithm just use the _BaseProvider_ `_getPackageDimensions()` method via

```php
parent::_getPackageDimensions($order)
```

## Configuration

Your newly created shipping provider should appear in the Postie settings as a new provider.

![My Shipping Provider](/docs/screenshots/my-shipping-provider.png)

Here you can adjust the name, setup API settings, add markup as well as rename and activate individual shipping services.