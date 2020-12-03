# Fastway
In order to use Fastway, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

### Connect to the Fastway API
1. Go to <a href="http://au.api.fastway.org/v2/docs/page/GetAPIKey.html" target="_blank">Fastway Developers Centre</a> and register for API access.
1. Copy the **API Key** from Fastway and paste in the **API Key** field below.

### Services
The below service are available with Fastway for domestic customer destination addresses.

- Road Parcel (Red)
- Road Parcel (Green)
- Local Parcel (Brown)
- Local Parcel (Black)
- Local Parcel (Blue)
- Local Parcel (Yellow)
- Shorthaul Parcel (Pink)
- National Network A2 Satchel
- National Network A3 Satchel
- National Network A4 Satchel
- National Network A5 Satchel

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'fastway'  => [
        'name' => 'Fastway',

        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],

        'services' => [
            'RED' => 'Road Parcel (Red)',
            'SAT_NAT_A2' => 'National Network A2 Satchel',
        ],
    ],
]
```