# Configuration

You can customise Postie’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `postie.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will change the name displayed in the control panel:

```php
<?php

return [
    'pluginName' => 'Postie Tools',
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `pluginName`

**Type:** `string` · **Default:** `'Postie'`

If you wish to customise the plugin name.
:::


::: reference
### `enableCaching`

**Type:** `bool` · **Default:** `true`

Whether to enable intelligent caching when fetching rates.
:::


::: reference
### `enableRouteCheck`

**Type:** `bool` · **Default:** `true`

Whether to enable route-checking to protect fetching live rates unnecessarily.
:::


::: reference
### `shippedOrderStatus`

**Type:** `string|null` · **Default:** `'shipped'`

The Order Status handle to be used to mark an order as shipped for Postie to update when lodging a shipment when printing labels.
:::


::: reference
### `partiallyShippedOrderStatus`

**Type:** `string|null` · **Default:** `'partiallyShipped'`

The Order Status handle to be used to mark an order as partially shipped for Postie to update when lodging a shipment when printing labels.
:::


::: reference
### `routesChecks`

**Type:** `array` · **Default:** `[ '/{cpTrigger}/commerce/orders/\d+', '/{actionTrigger}/commerce/orders/refresh', '/shop/shipping', '/shop/checkout/shipping', ]`

With `enableRouteCheck` enabled, only these routes will trigger fetching rates. Supports Regex and `{cpTrigger}`.
:::

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
