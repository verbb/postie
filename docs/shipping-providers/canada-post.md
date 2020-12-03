# Canada Post
In order to use Canada Post, you'll need to ensure you are using a valid Canadian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to CAD.

### Connect to the Canada Post API
1. Go to <a href="https://www.canadapost.ca/cpo/mc/business/productsservices/developers/services/gettingstarted.jsf" target="_blank">Canada Post Developers Centre</a> and register for API access.
1. Copy the **Customer Number** from Canada Post and paste in the **Customer Number** field in Postie.
1. Copy the **Username** from Canada Post and paste in the **Username** field in Postie.
1. Copy the **Password** from Canada Post and paste in the **Password** field in Postie.

### Services
The below service are available with Canada Post for domestic and international customer destination addresses.

- Domestic
    - Regular Parcel
    - Expedited Parcel
    - Xpresspost
    - Priority
    - Library Books
- USA
    - Expedited Parcel USA
    - Tracked Packet - USA
    - Tracked Packet USA (LVM)
    - Priority Worldwide Envelope USA
    - Priority Worldwide pak USA
    - Priority Worldwide parcel USA
    - Small Packet USA Air
    - Tracked Packet USA (LVM)
    - Xpresspost USA
- International
    - Xpresspost International
    - Tracked Packet - International
    - International Parcel Air
    - International Parcel Surface
    - Priority Worldwide envelope INTL
    - Priority Worldwide pak INTL
    - Priority Worldwide parcel INTL
    - Small Packet International Air
    - Small Packet International Surface

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