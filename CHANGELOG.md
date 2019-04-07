# Changelog

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
