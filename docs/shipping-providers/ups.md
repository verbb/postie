# UPS

## Connect to the UPS API
1. Go to <a href="https://www.ups.com/upsdeveloperkit?loc=en_US" target="_blank">UPS Developer Kit</a> and register for API access.
1. Copy the **API Key** from UPS and paste in the **API Key** field in Postie.
1. Copy the **Test API Key** from UPS and paste in the **Test API Key** field in Postie.
1. Copy the **Username** from UPS and paste in the **Username** field in Postie.
1. Copy the **Password** from UPS and paste in the **Password** field in Postie.
1. Copy the **Account Number** from UPS and paste in the **Account Number** field in Postie.

## Services
The below service are available with UPS for domestic and international customer destination addresses.

- Domestic
    - UPS Next Day Air Early AM
    - UPS Next Day Air
    - Next Day Air Saver
    - UPS Second Day Air AM
    - UPS Second Day Air
    - UPS Three-Day Select
    - UPS Ground
    - UPS Sure Post
    - UPS Next Day Air Early
    - UPS Next Day Air
    - UPS Next Day Air Saver
    - UPS Second Day Air A.M.
    - UPS Second Day Air
    - UPS Three-Day Select
    - UPS Ground
    - UPS Next Day Air Early (Saturday Delivery)
    - UPS Next Day Air (Saturday Delivery)
    - UPS Second Day Air (Saturday Delivery)
- International
    - UPS Standard
    - UPS Worldwide Express
    - UPS Worldwide Express Plus
    - UPS Worldwide Expedited
    - UPS Saver
    - UPS Access Point Economy
    - UPS Today Standard
    - UPS Today Dedicated Courier
    - UPS Today Intercity
    - UPS Today Express
    - UPS Today Express Saver
    - UPS Worldwide Express Freight
- EU-Based
    - UPS Worldwide Express Plus
    - UPS Worldwide Express
    - UPS Worldwide Express Saver
    - UPS Standard
    - UPS Worldwide Expedited
    - UPS Express Plus
    - UPS Express
    - UPS Express Saver
    - UPS Standard
    - UPS Express Plus
    - UPS Express
    - UPS Express Saver
    - UPS Standard
    - UPS Express NA 1
    - UPS Worldwide Express Plus
    - UPS Express
    - UPS Express Saver
    - UPS Expedited
    - UPS Standard

## Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'ups' => [
        'name' => 'USPS',

        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxx',
            'testApiKey' => 'xxxxxxxxxxxxxxxxxxxxx',
            'username' => 'xxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxx$',
        ],

        'services' => [
            'S_AIR_1DAYEARLYAM' => 'UPS Next Day Air Early AM',
            'S_AIR_1DAY' => 'UPS Next Day Air',
            'S_GROUND' => 'UPS Ground',
            'S_STANDARD' => 'UPS Standard',
        ],
    ],
]
```
