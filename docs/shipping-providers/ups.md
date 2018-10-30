# UPS

In order to use UPS, you'll need to ensure you are using a valid United States address as your origin. You'll also need to ensure your Craft Commerce default currency is set to USD.

### How to get API access

Register for an access key via the [UPS Developer Kit](https://www.ups.com/upsdeveloperkit?loc=en_US) form. Add this access key in the provider information either through the control panel, or in the configuration file (as the API Key).

### Services

The below service are available with UPS for domestic and international customer destination addresses.

- `S_AIR_1DAYEARLYAM`
- `S_AIR_1DAY`
- `S_AIR_1DAYSAVER`
- `S_AIR_2DAYAM`
- `S_AIR_2DAY`
- `S_3DAYSELECT`
- `S_GROUND`
- `S_SURE_POST`
- `S_STANDARD`
- `S_WW_EXPRESS`
- `S_WW_EXPRESSPLUS`
- `S_WW_EXPEDITED`
- `S_SAVER`
- `S_ACCESS_POINT`

### Control Panel

![UPS Provider](/uploads/plugins/postie/ups-provider.png)

### Configuration File

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'USPS' => [
        'name' => 'USPS',
        'markUpRate' => '<yourMarkUpRate>',
        'markUpBase' => '<value>',

        'settings' => [
            'apiKey' => '<apiKey>',
            'testApiKey' => '<testApiKey>',
            'username' => '<username>',
            'password' => '<password>',
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