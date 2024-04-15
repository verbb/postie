# Upgrading from v3
While the [changelog](https://github.com/verbb/postie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides a high-level overview and organizes changes by category.

## Manual Fetch Rates
Previously, Postie provided a means to fetch rates manually through the existence of a POST variable. Instead, we have changed this to allow for certain routes to only allow the fetching of rates, which is of course configurable.

To refresh why this is needed — as soon as your cart has a valid address, Commerce will prompt Postie to fetch shipping rates. This slows down the process of dealing with your cart, which is incredibly detrimental to the shopping experience.

This behaviour is enabled by default. You will need to add all routes where you wish to fetch shipping rates.

:::danger
This is an important step to check in your installation! If you have a custom path for your shipping page, this setting will need to be configured to reflect your checkout route.
:::

```php
<?php

return [
    '*' => [
        'enableRouteCheck' => true,
        'routesChecks' => [
            '/shop/checkout/shipping',
            '/shop/cart',
        ],
    ]
];
```

## Shippy Package
All shipping logic has now been extracted to a separate [verbb/shippy](https://github.com/verbb/shippy) package. This has been a major refactor of how providers work but is mostly under-the-hood work, with some exciting benefits such as tracking and label functionality. 

What this means is that this should not pose any changes to how Postie works, but if you've developed any custom providers, these will need to be updated to be compatible with Shippy.

## Providers
Providers are now stored in a dedicated project config set, rather than bundled with your `project.yaml` main file. This provides better performance for your yaml files, which previously stored settings for **every** provider whether they were enabled or not.

Secondly, providers are now created on-demand, rather than all being available to configure. You will need to create a new provider if you want to use one. This gives you the flexibility to create multiple providers. 

Postie's migration will have automatically created any enabled providers before the update, but it's worth checking that your providers and settings are in place. Some will also require some manual changes due to updated APIs.

### Config Files
Settings for a provider set via a `config/postie.php` configuration file have changed slightly. Settings are now "flat" rather than being in a `settings` key. For example:

```php
// Postie v3
'providers' => [
    'australiaPost' => [
        'name' => 'Australia Post',
        'settings'   => [
            'apiKey' => '•••••••••••••••••••••••••••••',
        ],
    ],
],

// Postie v4
'providers' => [
    'australiaPost' => [
        'name' => 'Australia Post',
        'apiKey' => '•••••••••••••••••••••••••••••',
    ],
],
```

### Weight and Dimensions
To save confusion and potential misconfiguration, we have removed the **Weight Unit** and **Dimension Unit** settings for all providers. This is now determined automatically for the provider and the origin country.

### Connection Testing
We have also removed the "Connection Tester" functionality to something more meaningful with the **Rates Testing** utility. This provides essentially the same functionality, with the additional benefit of being able to set the addresses and package for a rate request and see the rates that are reported back.

#### Australia Post
You can now specify the API you wish to use — PAC (rates only) or Shipping & Tracking (all APIS).

#### Bring
You can now specify the API you wish to use — rates only or all APIS.

#### Canada Post
You can now specify the API you wish to use — rates only or all APIS.

Old | What to do instead
--- | ---
| `useTestEndpoint` | Use `isProduction` instead.

#### DHL Express
You can now specify the API you wish to use — rates only or all APIS.

Old | What to do instead
--- | ---
| `useTestEndpoint` | Use `isProduction` instead.

#### FedEx
We now use the more modern APIs from FedEx, which include new client credentials for OAuth authentication. You will need to generate new API details following our [guide](docs:shipping-providers/fedex).

Old | What to do instead
--- | ---
| `useTestEndpoint` | Use `isProduction` instead.
| `key` | Use `clientId` and `clientSecret` for OAuth authentication.
| `password` | Use `clientId` and `clientSecret` for OAuth authentication.
| `meterNumber` | Use `clientId` and `clientSecret` for OAuth authentication.
| `enableOneRate` | Enabled by default.
| `enableFreight` | Use the **FedEx Freight** provider instead.
| `freightShipper*` | Use the **FedEx Freight** provider instead.
| `freightBilling*` | Use the **FedEx Freight** provider instead.

#### FedEx Freight
Previously, this was bundled with the FedEx provider, but to simply handling, it's been extracted to its own provider. This also helps to combine both freight and non-freight rates if you wish.

This includes the same changes to the main FedEx provider.

#### New Zealand Post

Old | What to do instead
--- | ---
| `useTestEndpoint` | Use `isProduction` instead.

#### Royal Mail
You can now specify the API you wish to use — rates only or all APIS.

#### Sendle

Old | What to do instead
--- | ---
| `useSandbox` | Use `isProduction` instead.

#### UPS
We now use the more modern APIs from UPS, which include new client credentials for OAuth authentication. You will need to generate new API details following our [guide](docs:shipping-providers/ups).

Old | What to do instead
--- | ---
| `useTestEndpoint` | Use `isProduction` instead.
| `negotiatedRates` | Determined through `accountNumber` being present.
| `apiKey` | Use `clientId` and `clientSecret` for OAuth authentication.
| `testApiKey` | Use `clientId` and `clientSecret` for OAuth authentication.
| `username` | Use `clientId` and `clientSecret` for OAuth authentication.
| `password` | Use `clientId` and `clientSecret` for OAuth authentication.
| `enableFreight` | Use the **UPS Freight** provider instead.

#### USPS
We now use the more modern APIs from USPS, which include new client credentials for OAuth authentication. You will need to generate new API details following our [guide](docs:shipping-providers/usps).

Old | What to do instead
--- | ---
| `username` | Use `clientId` and `clientSecret` for OAuth authentication.
| `password` | Use `clientId` and `clientSecret` for OAuth authentication.

## Control Panel Page
We now no longer show the Postie settings in the control panel navigation. This is because all Postie settings are "settings" and cannot be configured on a production environment where `allowAdminChanges = false`. As such, there's no need to show a page that users won't be able to access.

Postie settings can be managed via Settings > Postie as normal.

## Events
The following events have been removed as they are no longer applicable.

Old | What to do instead
--- | ---
| `Provider::EVENT_MODIFY_PAYLOAD` | Use `Provider::EVENT_BEFORE_FETCH_RATES` instead.
| `Provider::EVENT_MODIFY_RATES` | Use `Provider::EVENT_AFTER_FETCH_RATES` instead.
| `Provider::EVENT_MODIFY_SHIPPING_METHODS` | Use `Service::EVENT_BEFORE_REGISTER_SHIPPING_METHODS` instead.
| `PluginController::EVENT_MODIFY_VARIANT_QUERY` | Use `Postie::EVENT_MODIFY_VARIANT_QUERY` instead.


## Plugin Settings
The following plugin settings have changed.

Old | What to do instead
--- | ---
| `hasCpSection` | No configurable settings are available on non-development environments.
| `applyFreeShipping` | Free shipping is always applied is applicable.
| `displayDebug` | Use the new Postie Yii Debug pane.
| `displayErrors` | Use the new Postie Yii Debug pane.
| `displayFlashErrors` | Use the new Postie Yii Debug pane.
| `manualFetchRates` | No longer required thanks to improved caching.
| `fetchRatesPostValue` | As above.

