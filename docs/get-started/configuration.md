# Configuration
Create a `postie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Postie, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Postie',
        'enableCaching' => true,
        'enableRouteCheck' => true,
        'routesChecks' => [
            '/{cpTrigger}/commerce/orders/\d+',
            '/checkout/shipping',
            '/shop/checkout/shipping',
        ],
        'providers' => [],
    ]
];
```

## Configuration options
- `pluginName` - If you wish to customise the plugin name.
- `enableCaching` - Whether to enable intelligent caching when fetching rates.
- `enableRouteCheck` - Whether to enable route-checking to protect fetching live rates unnecessarily.
- `routesChecks` - With `enableRouteCheck` enabled, only these routes will trigger fetching rates. Supports Regex and `{cpTrigger}`.
- `providers` - A collection of options for each provider.

### Providers
Supply your client configurations as per the below. Must be keyed with the handle for the provider.

```php
'providers' => [
    'australiaPost' => [
        'name' => 'AusPost',
        'enabled' => true,
        'isProduction' => false,
        'apiKey' => '•••••••••••••••••••••••••••••',

        // Markup
        'markUpRate' => '10',
        'markUpBase' => 'value',

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
]
```

- `name` - What you wish to call this provider.
- `enabled` - Whether this provider is enabled.
- `isProduction` - Whether this provider should make calls to the Production API (some providers have testing and production APIs, but not all).
- `markUpRate` - If specifying a markup amount, provide it here.
- `markUpBase` - What the markup rate should be. Either `percentage` or `value`.
- `packingMethod` - The packing method for box-packing calculation. Either `perItem`, `boxPacking` or `singleBox`.
- `services` - A list of all enabled services, keyed by their service handle, and value of what you'd like to call it. Consult each providers `getServiceList()` function for options.

#### Services
You can also expand the `services` setting to include additional information.

```php
'services' => [
    'AUS_PARCEL_EXPRESS' => [
        'enabled' => true,
        'name' => 'Express Post (1-2 Days)',
        'shippingCategories' => [
            4 => [
                'condition' => 'disallow',
            ],
        ],
    ],
],
```

Note that the array index `4` in this case refers to your Shipping Category ID, not just the array index.
