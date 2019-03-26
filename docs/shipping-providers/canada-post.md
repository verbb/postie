# Canada Post

In order to use Canada Post, you'll need to ensure you are using a valid Canadian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to CAD.

### How to get API access

Register for API credentials via the [Canada Post Developers Centre](https://www.canadapost.ca/cpo/mc/business/productsservices/developers/services/gettingstarted.jsf) form.

### Services

The below service are available with Canada Post for domestic and international customer destination addresses.

- Domestic

    - `DOM_EP`
    - `DOM_RP`
    - `DOM_PC`

- International

    - `DOM_XP`
    - `INT_PW_ENV`
    - `USA_PW_ENV`
    - `USA_PW_PAK`
    - `INT_PW_PAK`
    - `INT_PW_PARCEL`
    - `USA_PW_PARCEL`
    - `INT_XP`
    - `INT_IP_AIR`
    - `INT_IP_SURF`
    - `INT_TP`
    - `INT_SP_SURF`
    - `INT_SP_AIR`
    - `USA_XP`
    - `USA_EP`
    - `USA_TP`
    - `USA_SP_AIR`

### Configuration

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'canadaPost'  => [
        'name' => 'Canada Post',

        'settings' => [
            'customerNumber' => 'xxxxxxxxxxxxx',
            'username' => 'xxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxx',
        ],

        'services' => [
            'DOM_EP' => 'Expedited Parcel',
            'DOM_RP' => 'Regular Parcel',
            'DOM_PC' => 'Priority',
            'INT_PW_ENV' => 'Priority Worldwide envelope INTL',
            'USA_PW_ENV' => 'Priority Worldwide envelope USA',
        ],
    ],
]
```