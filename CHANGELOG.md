# Changelog

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
