# Fastway

In order to use Fastway, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

### How to get API access

Register for API credentials via the [Fastway Developers Centre](http://au.api.fastway.org/v2/docs/page/GetAPIKey.html) form.

### Services

The below service are available with Fastway for domestic customer destination addresses.

- `RED`
- `GREEN`
- `BROWN`
- `BLACK`
- `BLUE`
- `YELLOW`
- `PINK`
- `SAT_NAT_A2`
- `SAT_NAT_A3`
- `SAT_NAT_A4`
- `SAT_NAT_A5`

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