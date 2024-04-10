# Changelog

## 3.1.7 - 2024-04-10

### Added
- Add dimensions to packages for Bring.

### Changed
- Update Bring services to latest available (2023+).
- Change minimum packed box weight to 1 gram (converted to the providers weight unit).

### Fixed
- Fix an error with USPS international shipments.

## 3.1.6 - 2024-03-18

### Changed
- Update Bring API compatibility.
- Allow `Provider::EVENT_BEFORE_PACK_ORDER` to override the box packing provider.

### Fixed
- Fix an error with New Zealand post settings.

## 3.1.5 - 2024-03-04

### Changed
- The variants-shippable check now only queries 100 variants by default.

## 3.1.4 - 2023-10-25

### Changed
- Updated Royal Mail rates.

## 3.1.3 - 2023-09-25

### Added
- Add Declared Value Option to UPS. (thanks @bryanredeagle).
- Add support for packed boxes to have their price correctly depending on their packed items.

### Changed
- Updated Royal Mail rates.

### Fixed
- Fix an error when migrating from Craft 3.

## 3.1.2 - 2023-08-31

### Fixed
- Fix UPS and negotiated rates throwing an error.
- Fix an error for some providers and `boxSizes`.

## 3.1.1 - 2023-08-17

### Added
- Added “Pickup Type” for UPS, which was previously removed.

### Fixed
- Fix an error when migrating UPS to new provider.

### Removed
- Removed “Use Negotiated Rates” for UPS (applied by default).

## 3.1.0 - 2023-08-16

### Added
- Add support for new UPS API.
- Add support for products marked with “Free Shipping” to be excluded from rates calls.

### Changed
- Check discounts and free shipping when getting line items used in fetching rates. (thanks @zollf).

### Deprecated
- Deprecated existing UPS providers to UPS (Legacy).

## 3.0.3 - 2023-05-27

### Added
- Add support for UPS negotiated freight rates.
- Add “Use Test Endpoint” setting for UPS.

### Changed
- Only admins are now allowed to access plugin settings

### Fixed
- Fix type check for PHP 7.x.
- Fix a memory issue with UPS Freight services.
- Fix FedEx freight not correctly setting 150lb minimum weight.
- Fix an error with Royal Mail rates. (thanks @MadMikeyB).
- Fix an issue for FedEx freight rates in some cases.
- Fix an issue where shipping rates weren’t always available in checkout.
- Fix an error when fetching UPS Freight rates.

## 3.0.2 - 2022-12-15

### Changed
- Update TNT package dimensions to use `ceil()`.

### Fixed
- Fix `manualFetchRates` not working correctly for multiple providers.
- Fix TNT Australia rates not working correctly.
- Fix an error with USPS.
- Fix “Box Sizes” setting for providers not working correctly.
- Fix `ModifyRatesEvent` response typing.
- Fix an error with Fastway and FedEx with country code.
- Fix an error where some providers weren’t having their config values overridden via config files.

## 3.0.1 - 2022-10-24

### Added
- Add more USPS rates and fix First-Class rates.

### Changed
- Revamp USPS services to better match their codes and available services.
- Update some Royal Mail international rates.

### Fixed
- Fix `ModifyPayloadEvent` typing
- Fix an error when saving some providers and their `boxSizes` setting

## 3.0.0 - 2022-08-19

### Changed
- Now requires PHP `8.0.2+`.
- Now requires Craft `4.0.0+`.
- Now requires Craft Commerce `4.0.0+`.
- Now requires Postie `2.2.7` in order to update from Craft 3.
- `Provider::supportsDynamicServices()` is now a static function.
- `Provider::getServiceList()` is now a static function.

### Fixed
- Fix Commerce/Craft deprecations.
- More Commerce `^4.0.0` compatibility fixes.

## 2.4.22 - 2023-05-27

### Added
- Add support for UPS negotiated freight rates.
- Add “Use Test Endpoint” setting for UPS.

### Fixed
- Fix type check for PHP 7.x.
- Fix a memory issue with UPS Freight services.
- Fix FedEx freight not correctly setting 150lb minimum weight.
- Fix an issue for FedEx freight rates in some cases.
- Fix an issue where shipping rates weren’t always available in checkout.
- Fix an error when fetching UPS Freight rates.

## 2.4.21 - 2022-12-15

### Changed
- Update TNT package dimensions to use `ceil()`.

### Fixed
- Fix `manualFetchRates` not working correctly for multiple providers.
- Fix TNT Australia rates not working correctly.

## 2.4.20 - 2022-10-24

### Added
- Add more USPS rates and fix First-Class rates.

### Changed
- Revamp USPS services to better match their codes and available services.
- Update some Royal Mail international rates.

## 2.4.19 - 2022-05-24

### Fixed
- Fix an error when trying to determine cached shipping rates.

## 2.4.18 - 2022-04-09

### Fixed
- Fix an error with `SinglePackageProvider` and `EVENT_BEFORE_FETCH_RATES` incorrectly serializing packed boxes.

## 2.4.17 - 2022-04-07

### Fixed
- Remove deprecated `dimensions` in `FetchRatesEvent`.

## 2.4.16 - 2022-04-01

### Changed
- Update Royal Mail pricing to April 2022.
- Improve memoization implementation for rate-fetching, when `enableCaching` is disabled.
- Update `EVENT_BEFORE_FETCH_RATES` to use `packedBoxes` instead of `dimensions`. Refer to updated docs.

### Fixed
- Fix box-packing line items that have no dimensions or weight.
- Fix order totals used for insurance for multiple providers. Now only uses packed items for the insured total rather than the entire order total.
- Fix lack of international shipping handling for Canada Post.
- Fix an error for some providers when a country hasn't been set on the cart.

## 2.4.15 - 2021-12-31

### Added
- Add shipper address to UPS provider for negotiated rates.

### Changed
- Use query batching for product helper.
- Ensure products helper only shows variants with dimensions enabled for their product type.

### Fixed
- Fix UPS throwing an error in some cases due to lack of rounding for dimensions and weight.

## 2.4.14 - 2021-12-11

### Added
- Add UPS Freight support.

### Fixed
- Fix Australia Post international rates (from 2.4.13).

## 2.4.13 - 2021-12-08

### Added
- Add "Apply Free Shipping" plugin setting to apply free shipping on returned rates, if all items have free shipping set.
- Add "Include VAT" setting for Royal Mail.
- Add `itemValue` to boxes for packing constraints.

### Changed
- Update Royal Mail pricing for 2021.

### Fixed
- Fix Australia Post not returning international rates.
- Ensure markup rates are only applied if the shipping rate is greater than zero.
- Fix static providers not always returning the cheapest box available when box-packing.
- Fix Bring provider link.

## 2.4.12 - 2021-11-21

### Fixed
- Remove testing shipping rates from RoyalMail.

## 2.4.11 - 2021-10-28

### Changed
- Update handling of completed orders to return the correct rate at time of completion. This fixes incorrect costs when recalculating an order total.

### Fixed
- All available shipping rates are now returned when "Recalculate order" is clicked, when editing an order in the control panel.

## 2.4.10 - 2021-09-22

### Fixed
- Ensure packed boxes have a minimum weight, as 0 weighted boxes are issues for most providers.

## 2.4.9 - 2021-08-22

### Added
- Add support for estimated shipping addresses for guests.

### Changed
- Update `gabrielbull/ups-api` dependancy to `^1.0.0` for Guzzle 7 support.

### Fixed
- Fix Australia Post when testing connection using non kg/cm units.

## 2.4.8 - 2021-06-29

### Added
- Add `Service::EVENT_BEFORE_REGISTER_SHIPPING_METHODS` for modifying the master list of shipping methods before they get handed off to Commerce. (thanks @michaelrog).
- Add `Provider::EVENT_MODIFY_SHIPPING_METHODS`.

### Changed
- Change product summary to limit to 20 variants for performance.

### Fixed
- Fix `Provider::EVENT_MODIFY_PAYLOAD` not allowing `payload` to be overridden. (thanks @richrawlings).
- Fix removing box sizes for disabled providers when saving.
- Fix providers triggering validation when disabled.

## 2.4.7 - 2021-02-20

### Fixed
- Fix USPS rates not factoring in correct pricing for multiple boxes.
- Fix when updating services, shipping category conditions would be lost.

## 2.4.6 - 2021-01-28

### Changed
- All providers now round box dimensions and weights to 2 decimal places.
- Improve project config storage for settings. Postie will now no longer save (some) settings for disabled providers.

### Fixed
- Fix DHL Express throwing errors due to invalid weight/dimensions.
- Fix in-memory caching not working correctly for providers. This meant potentially multiple requests for a single page request were being performed.

## 2.4.5 - 2021-01-16

### Added
- Add support for FedEx Freight.
- Allow providers connection check to be run from cron, or similar means.

### Fixed
- Fix potential error with AusPost International.
- Fix USPS not logging error messages correctly for domestic shipments.
- Fix USPS rates not reporting back correctly when using potentially invalid postcodes.
- Fix testing connection only using saved values, not values as you change them.
- Fix UPS connection testing for some non-US based accounts.
- Ensure Postie isn’t shown in the CP sidebar menu when `allowAdminChanges = false`.

## 2.4.4 - 2021-01-05

### Fixed
- Fix error when calculating rates for Australia Post, New Zealand Post and Sendle.

## 2.4.3 - 2020-12-22

### Added
- Add weight and dimension unit settings for each provider. Some providers (UPS) rely on units being set for the appropriate account's region.

### Fixed
- Fix potential error with Australia Post.
- Fix an error with UPS when using negotiated rates.
- Ensure FedEx formats dimension and weight units correctly.

## 2.4.2 - 2020-12-04

### Fixed
- Fix error introduced in 2.4.1 when saving settings form the control panel.

## 2.4.1 - 2020-12-04

### Fixed
- Fix potential migration issue if no providers were configured.

## 2.4.0 - 2020-12-03

### Added
- Add Sendle shipping provider.
- Add Interparcel shipping provider.
- Add New Zealand Post shipping provider.
- Add Bring shipping provider.
- Add Royal Mail shipping provider.
- Add PostNL shipping provider.
- Add Colissimo shipping provider.
- Add new 4D bin-packing algorithm to more accurately pack your boxes.
- Add ability to define box dimensions and weights for each provider, so you can better split order items into boxes.
- Add "Packing Method" setting for all providers.
- Add "Pack items individually" packing method, allowing all line items and quantities to be boxes individually.
- Add "Pack items into boxes" packing method, allowing provider-supplied boxes, or user-created ones.
- Add "Pack items into a single box" packing method - a slightly improved version of the box-packing algorithm in pre 2.4.0. This ensures a non-breaking change to box-packing behaviour to date.
- Add `EVENT_BEFORE_PACK_ORDER` and `EVENT_AFTER_PACK_ORDER` events to all providers, allowing modification of the box-packing logic.
- Add "Restrict Shipping Methods" setting for all providers. This allows opt-out of restricting to certain shipping services, and always use whatever is returned by the provider. This is particularly beneficial for some providers where services can't always be determined.
- Add `weightUnit` and `dimensionUnit` to each provider for consistent use with boxes. These should always be provided in grams.
- Add `supportsDynamicServices()` to providers whose list of services isn’t statically defined.
- Add `getMaxPackageWeight()` to providers, to define what their maximum package weight is.
- Add `getIsInternational()` to providers, to return whether an order is considered domestic or international.
- Add provider setup instructions on connecting to the respective APIs.
- Add ability for providers to define their own default boxes of dimensions and types that cannot be deleted, but still toggled enabled/disabled.
- Add letter rates to Australia Post (domestic and international). Now fetches rates for letters, for applicable products, and as defined in the new box definitions.
- Add some additional missing satchel rates for Australia Post.
- Add “Residential Address” setting for UPS.
- Add “Include Insurance” setting for UPS.
- Add “Residential Address” setting for Fedex.
- Add “Include Insurance” setting for Fedex.
- Add “Fedex One Rate” setting for Fedex.
- Add “Additional Options” to Canada Post.
- Add `SinglePackageProvider` class for providers to extend from, if the API doesn't support sending multiple packages in one request. This class will fetch the first box, and add each subsequent (cached) response for all other boxes that are identical.
- Add support for all providers to use env variables in their settings.
- Add API connection testing for all providers, allowing you to troubleshoot API credentials before getting to shipping.
- Add `supportsConnection()`, `checkConnection()` and `getIsConnected()` methods to providers.
- Add testing classes for addresses and packages. Can be freely used to fetch a range of different values for testing responses.

### Changed
- Postie now requires PHP 7.1+.
- Multiple packages are now possible for Australia Post, DHL Express and TNT Australia. This not only improves a "too large to ship" response, but should provide more accurate pricing overall. Previously, Postie bundled all items in a single package, which would often go over maximum dimensions/weights.
- Existing providers now use the "Pack items into a single box" box packing algorithm. This is essentially the same as previous versions to prevent a breaking change.
- Tidied up the shipping methods table for providers.
- Some internal cleanup with providers (may affect custom providers). Refer to the [updated docs](https://verbb.io/craft-plugins/postie/docs/developers/provider).
- Provide field instructions for a number of provider settings.
- Visually highlight provider API instructions.

### Fixed
- Fix settings sidebar overflow in some cases.

### Removed
- Removed `Provider::getPackageDimensions()`.
- Removed `Provider::getDimensions()`.
- Removed `Provider::getSplitBoxWeights()`.

## 2.3.6 - 2020-12-02

### Added
- Add some additional Fedex rates for international distribution.

### Changed
- Update `jeremy-dunn/php-fedex-api-wrapper` to 4.0.

### Fixed
- Fix UPS using Canadian origin address not working correctly.
- Fix package dimensions not calculating correctly in some instances.

## 2.3.5 - 2020-10-30

### Added
- Add `shipDate` and `shipTime` for DHL Express.
- Add `modifyPayload` event to allow modifying the payload before it’s sent to providers to fetch rates.

## 2.3.4 - 2020-10-24

### Added
- Add missing Fedex UK Domestic services.
- Add Pickup Type option for UPS. You may want to change this to “Customer Counter” for more accurate UPS results, but do test this for your own needs. The default is left as-is (“Daily Pickup”) so as not to be a breaking change.

## 2.3.3 - 2020-10-16

### Added
- Add option to UPS to configure whether signatures are required.

### Fixed
- Fix defining provider settings in both the control panel and config files.

## 2.3.2 - 2020-10-02

### Changed
- Change Fedex package units to imperial (lb/in) to ensure proper rates are fetched.

### Fixed
- Fix Fedex/UPS/USPS maximum package weights calculating incorrectly.

## 2.3.1 - 2020-09-24

### Added
- Add DHL Express.
- Add handling for Fedex/UPS/USPS for maximum package weights, splitting into multiple packages.
- Add `displayFlashErrors` config setting. Allows errors from the provider APIs to be shown in checkout templates.
- Allow orders created in the control panel to bypass the `manualFetchRates` config setting, so they can pick a shipping method.
- Add `EVENT_MODIFY_VARIANT_QUERY` to modify the variants used to check weight and dimensions for in Postie's settings.

### Fixed
- Fixed an error when viewing an order in the control panel, that used Postie-provided shipping method. (thanks @keyurshah).

## 2.3.0 - 2020-08-09

### Changed
- Now requires Commerce 3.2+ and Craft 3.5+.

### Fixed
- Fixed error with Commerce 3.2.

## 2.2.9 - 2020-08-04

### Changed
- Ensure the lowest amount is always used for Fedex amounts.

## 2.2.8 - 2020-07-26

### Fixed
- Fix USPS error when a postcode isn’t set on the shipping address.

## 2.2.7 - 2020-07-14

### Fixed
- Fix UPS error for carts that have no shipping country selected.

## 2.2.6 - 2020-07-10

### Fixed
- Fix UPS throwing an error when the recipient address is from non-US countries.

## 2.2.5 - 2020-06-17

#### Fixed
- Fix error in UPS provider related to SurePost.

## 2.2.4 - 2020-06-16

#### Fixed
- Fix UPS SurePost exception preventing additional rates from being fetched. (thanks @Mosnar).

## 2.2.3 - 2020-05-26

### Fixed
- Add special-case for completed orders, and fetching non-live-rate shipping methods. This allows the correct use of `order.shippingMethod.name`. Please note that calling this for completed orders will report all Postie-provided shipping method costs as 0. As such, use the shipping costs recorded on the order (`order.totalShippingCost()`).
- Fix errors for console or queue requests.

## 2.2.2 - 2020-05-15

### Added
- Provide local cache for Australia Post countries API call (when the resource is offline).

## 2.2.1 - 2020-05-10

### Fixed
- Ensure we check for cached rates when manualFetchRates is turned on. Otherwise, the shipping method won't save on cart, or persist on page load.
- Remove duplicate cakephp/utility composer package. (thanks @codebycliff).
- Fix saving shipping method settings not working.

## 2.2.0 - 2020-05-03

### Added
- Added `manualFetchRates` config option, to allow you to manage manually fetching rates on-demand. Read the [docs](https://verbb.io/craft-plugins/postie/docs/setup-configuration/manually-fetching-rates) for more info.

### Changed
- Greatly improve caching mechanism for initial requests to providers. This should result in faster rates-fetching.
- Provider function `getSignature` is now public.

## 2.1.4 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 2.1.3 - 2020-04-15

### Added
- Add support for UPS “Sure Post”.

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

## 2.1.2 - 2020-03-17

### Fixed
- Canada Post - Fix incorrect URL for live requests.
- Fix styling issues for provider markup settings.

## 2.1.1 - 2020-01-18

### Added
- Add `ShippingMethod::EVENT_MODIFY_SHIPPING_RULE`. See [docs](https://verbb.io/craft-plugins/postie/docs/developers/events).

## 2.1.0 - 2020-01-09

### Added
- Add TNT Australia provider.
- Add 2- and 3-day Priority options to USPS. (thanks @AugustMiller).
- Add `Order` object to `ModifyRatesEvent`. (thanks @AugustMiller).
- Add `beforeFetchRates` event.

### Changed
- Update FedEx for Ground Transit Time. FedEx handles the delivery date for Ground different than Express. For Ground, they use `TransitTime`. (thanks @keyurshah).

### Fixed
- Fix provider icon error for custom provider.
- Fix USPS/UPS handles, incorrectly being set as `uSPS` and `uPS`.
- Fix incorrect caching of rates for multiple providers.
- Fix zero-based rates not being shown to pick during checkout.
- Fix AusPost and Canada post error handling.

## 2.0.8 - 2019-08-17

### Fixed
- Remove provider settings from shipping method requests, particularly for XHR.
- Fix debug statements occurring for non-site requests.

## 2.0.7 - 2019-08-16

### Added
- Add support for Commerce 3.
- Add more UPS services, and change the way UPS services match.

## 2.0.6 - 2019-07-16

### Fixed
- Fix provider settings not being populated on shipping methods and rules. Meant markup rates weren't working correctly.

## 2.0.5 - 2019-07-13

### Added
- Add `modifyRates` providing access to the raw response from a provider and the extracted shipping rates. See [docs](https://verbb.io/craft-plugins/postie/docs/developers/events#the-modifyRates-event).
- All shipping rates now have additional options available on the shipping rule. See [docs](https://verbb.io/craft-plugins/postie/docs/setup-configuration/displaying-rates#rate-options).
- Add negotiated rate support for UPS.

### Fixed
- Fix error with store location state for UPS.

## 2.0.4 - 2019-06-01

### Added
- Add `delayFetchRates`, `manualFetchRates` and `fetchRatesPostValue`.

### Changed
- Improve in-memory caching.

### Fixed
- Fix memory issues in certain cases when fetching rates.
- Tweak state handling for Fedex.

## 2.0.3.1 - 2019-04-10

### Fixed
- Remove leftover debugging.

## 2.0.3 - 2019-04-10

### Fixed
- Fix return type incompatibility causing errors.
- Fix dimensions API issue with Canada Post.
- Improve response error handling for Canada Post.
- Fix lack of formatting handling for Canada Post zip codes.

## 2.0.2 - 2019-04-07

### Fixed
- Swap XML parser for Canada Post.
- Fix missing shipping description.

## 2.0.1 - 2019-03-27

### Fixed
- Fix some error messages themselves throwing errors.

## 2.0.0 - 2019-03-26

### Added
- Craft 3/Commerce 2 support.
- Add Canada Post provider.
- Add Fastway provider.
- Add initial TNT provider. Please contact us with API account details to finalise!
- Add `displayDebug` config setting.
- Add `displayErrors` config setting.
- Add `enableCaching` config setting.
- Add `enabled` config setting for each provider.
- Add provider icons, and CP UI improvements.

### Changed
- Updated provider functions for easier/clearer extendability. See docs.
- Updated cache mechanism for better performance.
- Australia Post now fetches shipping rates in a single API call. 
- Removed `originAddress` config setting in favour of Commerce's `Store Location`.
- Provider handles in config file are now required to be provided in camel case.

## 1.0.2 - 2018-08-01

### Added
- Add config setting to disable cache.
- Add UPS Provider.

### Fixed
- Fedex - Add config setting `disableCache` for test endpoint (not default when using DevMode).
- Fedex - fix services from pre1.0.1 causing issues.

## 1.0.1 - 2018-01-22

### Added
- Add management of shipping category conditions for shipping methods.

## 1.0.0 - 2017-12-11

- Initial release.
