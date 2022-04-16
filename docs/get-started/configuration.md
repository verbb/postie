# Configuration
Create a `postie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Postie, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Postie',
        'hasCpSection' => false,
        'enableCaching' => true,
        'displayDebug' => false,
        'displayErrors' => false,
        'manualFetchRates' => false,
        'fetchRatesPostValue' => 'postie-fetch-rates',
        'providers' => [],
    ]
];
```

### Configuration options
- `pluginName` - If you wish to customise the plugin name.
- `hasCpSection` - Whether to have the plugin pages appear on the main CP sidebar menu.
- `applyFreeShipping` - Whether to apply free shipping if all items in the cart have been marked with "Free Shipping".
- `enableCaching` - Whether to enable intelligent caching when fetching rates.
- `displayDebug` - Whether to display debugging when fetching rates.
- `displayErrors` - Whether to display errors when fetching rates.
- `manualFetchRates` - Whether to fetch rates manually. Refer to [Manually Fetching Rates](docs:setup-configuration/manually-fetching-rates).
- `fetchRatesPostValue` - Specify a POST param value for manually fetching rates. Refer to [Manually Fetching Rates](docs:setup-configuration/manually-fetching-rates).
- `providers` - A collection of options for each provider.

#### Providers
Supply your client configurations as per the below. Must be keyed with the handle for the provider (camel-cased name).

```php
'providers' => [
    'australiaPost' => [
        'name' => 'AusPost',
        'enabled' => true,

        // API Settings
        'settings'   => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],

        // Mark-Up
        'markUpRate' => '10',
        'markUpBase' => 'value',

        // Units
        'weightUnit' => 'g',
        'dimensionUnit' => 'cm',

        // Packing method
        'packingMethod' => 'boxPacking',

        // List of provided services
        'services' => [
            'AUS_PARCEL_EXPRESS' => 'Express Post',
            'AUS_PARCEL_EXPRESS_SATCHEL_500G' => 'Express Post Small Satchel',
            'AUS_PARCEL_REGULAR' => 'Parcel Post',
            'AUS_PARCEL_REGULAR_SATCHEL_500G' => 'Parcel Post Small Satchel',
        ],
    ],
    'fedEx' => [
        'settings' => [
            'accountNumber' => 'xxxxxxxxxxxxx',
            'meterNumber' => 'xxxxxxxxxxxxx',
            'key' => 'xxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxx',
            'useTestEndpoint' => true,
        ],
    ],
    'usps' => [
        'settings' => [
            'username' => 'xxxxxxxxxxxxx',
        ],
    ],
    'ups' => [
        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxx',
            'testApiKey' => 'xxxxxxxxxxxxxxxxxxxxx',
            'username' => 'xxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxx$',
        ],
    ],
    'canadaPost'  => [
        'settings' => [
            'customerNumber' => 'xxxxxxxxxxxxx',
            'username' => 'xxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
    'fastway'  => [
        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
]
```

- `name` - What you wish to call this provider.
- `enabled` - Whether this provider is enabled.
- `settings` - Depending on the provider, this will be an array of API settings.
- `markUpRate` - If specifying a markup amount, provide it here.
- `markUpBase` - What the markup rate should be. Either `percentage` or `value`.
- `weightUnit` - What weight unit should rate requests be sent to the provider. Each provider varies, but are typically `g`, `lb`, `kg`.
- `dimensionUnit` - What dimension unit should rate requests be sent to the provider. Each provider varies, but are typically `mm`, `cm`, `m`, `ft`, `in`.
- `packingMethod` - The packing method for box-packing calculation. Either `perItem`, `boxPacking` or `singleBox`.
- `services` - A list of all enabled services, keyed by their service handle, and value of what you'd like to call it. Consult each providers `getServiceList()` function for options.
