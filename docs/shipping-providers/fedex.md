# FedEx

In order to use FedEx, you'll need to ensure you are using a valid United States address as your store location. You'll also need to ensure your Craft Commerce default currency is set to USD.

### How to get API access

To use Fedex API, you'll need to:

1.  Create a profile at the [FedEx website](https://www.fedex.com/login/web/jsp/contactInfo1.jsp)
2.  Register for [FedEx Web Services Production Access](https://www.fedex.com/wpor/web/jsp/commonTC.jsp)

After this, you should receive an email with the following information, that is required by Postie:

- `accountNumber`
- `meterNumber`
- `key`
- `password`

### Services

The below service are available with FedEx for domestic and international customer destination addresses.

- Domestic
    
    - `FEDEX_1_DAY_FREIGHT`
    - `FEDEX_2_DAY`
    - `FEDEX_2_DAY_AM`
    - `FEDEX_2_DAY_FREIGHT`
    - `FEDEX_3_DAY_FREIGHT`
    - `FEDEX_EXPRESS_SAVER`
    - `FEDEX_FIRST_FREIGHT`
    - `FEDEX_FREIGHT_ECONOMY`
    - `FEDEX_FREIGHT_PRIORITY`
    - `FEDEX_GROUND`
    - `FIRST_OVERNIGHT`
    - `PRIORITY_OVERNIGHT`
    - `STANDARD_OVERNIGHT`
    - `GROUND_HOME_DELIVERY`
    - `SMART_POST`

- International
    
    - `INTERNATIONAL_ECONOMY`
    - `INTERNATIONAL_ECONOMY_FREIGHT`
    - `INTERNATIONAL_FIRST`
    - `INTERNATIONAL_PRIORITY`
    - `INTERNATIONAL_PRIORITY_FREIGHT`
    - `EUROPE_FIRST_INTERNATIONAL_PRIORITY`

### Configuration

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'fedEx' => [
        'name' => 'FedEx',

        'settings' => [
            'accountNumber' => 'xxxxxxxxxxxxx',
            'meterNumber' => 'xxxxxxxxxxxxx',
            'key' => 'xxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxx',
            'useTestEndpoint' => true,
        ],

        'services' => [
            'FEDEX_EXPRESS_SAVER' => 'Express Saver',
            'FEDEX_GROUND' => 'Ground',
            'INTERNATIONAL_ECONOMY' => 'International Economy',
            'INTERNATIONAL_PRIORITY' => 'International Priority',
        ],
    ],
]
```