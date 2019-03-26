# Australia Post

In order to use Australia Post, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

### How to get API access

Register for an API key via the [Australia Post developers](https://developers.auspost.com.au/apis/pacpcs-registration) website. Use this API Key in the provider information either through the control panel, or in the configuration file.

### Services

The below service are available with Australia Post for domestic and international customer destination addresses.

- Domestic
    
    - `AUS_PARCEL_COURIER`
    - `AUS_PARCEL_COURIER_SATCHEL_MEDIUM`
    - `AUS_PARCEL_EXPRESS`
    - `AUS_PARCEL_EXPRESS_SATCHEL_500G`
    - `AUS_PARCEL_REGULAR`
    - `AUS_PARCEL_REGULAR_SATCHEL_500G`
- International
    
    - `INT_PARCEL_COR_OWN_PACKAGING`
    - `INT_PARCEL_EXP_OWN_PACKAGING`
    - `INT_PARCEL_STD_OWN_PACKAGING`
    - `INT_PARCEL_AIR_OWN_PACKAGING`
    - `INT_PARCEL_SEA_OWN_PACKAGING`


### Configuration

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'australiaPost' => [
        'name' => 'Australia Post',

        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],

        'services'   => [
            'AUS_PARCEL_EXPRESS' => 'Express Post',
            'AUS_PARCEL_REGULAR' => 'Parcel Post',
        ],
    ],
]
```