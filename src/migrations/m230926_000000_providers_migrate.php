<?php
namespace verbb\postie\migrations;

use verbb\postie\Postie;
use verbb\postie\providers;

use Craft;
use craft\db\Migration;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;

class m230926_000000_providers_migrate extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.postie.schemaVersion', true);

        if (version_compare($schemaVersion, '2.2.3', '>=')) {
            return true;
        }

        $newProviders = [];

        // Update the project config for stencils.
        if ($providers = $projectConfig->get('plugins.postie.settings.providers')) {
            $providers = ProjectConfig::unpackAssociativeArray($providers);

            foreach ($providers as $handle => $provider) {
                $name = $provider['name'] ?? 'null';
                $enabled = StringHelper::toBoolean(($provider['enabled'] ?? ''));
                $markUpRate = $provider['markUpRate'] ?? null;
                $markUpBase = $provider['markUpBase'] ?? null;
                $packingMethod = $provider['packingMethod'] ?? null;
                $boxSizes = $provider['boxSizes'] ?? null;
                $restrictServices = $provider['restrictServices'] ?? null;
                $services = $provider['services'] ?? null;
                $settings = $provider['settings'] ?? [];

                $classMap = [
                    'australiaPost' => providers\AustraliaPost::class,
                    'bring' => providers\Bring::class,
                    'canadaPost' => providers\CanadaPost::class,
                    'colissimo' => providers\Colissimo::class,
                    'dhlExpress' => providers\DHLExpress::class,
                    'fastway' => providers\Fastway::class,
                    'fedEx' => providers\FedEx::class,
                    'interparcel' => providers\Interparcel::class,
                    'newZealandPost' => providers\NewZealandPost::class,
                    'postNl' => providers\PostNL::class,
                    'royalMail' => providers\RoyalMail::class,
                    'sendle' => providers\Sendle::class,
                    'tntAustralia' => providers\TNTAustralia::class,
                    'ups' => providers\UPS::class,
                    'usps' => providers\USPS::class,
                ];

                // Normalize some settings
                if ($handle === 'canadaPost') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                }

                if ($handle === 'dhlExpress') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                    $settings['clientId'] = 'tbc';
                    $settings['shipDate'] = $settings['shipDate']['date'] ?? null;
                    $settings['shipTime'] = $settings['shipTime']['time'] ?? null;
                    ArrayHelper::rename($settings, 'account', 'accountNumber');
                }

                if ($handle === 'fedEx') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                    $settings['clientId'] = 'tbc';
                    $settings['clientSecret'] = 'tbc';
                    ArrayHelper::remove($settings, 'key');
                    ArrayHelper::remove($settings, 'password');
                    ArrayHelper::remove($settings, 'meterNumber');
                    ArrayHelper::remove($settings, 'residentialAddress');
                    ArrayHelper::remove($settings, 'includeInsurance');
                    ArrayHelper::remove($settings, 'enableOneRate');

                    $freight = ArrayHelper::remove($settings, 'enableFreight');
                    $freightAccountNumber = ArrayHelper::remove($settings, 'freightAccountNumber');
                    $freightBillingStreetAddress = ArrayHelper::remove($settings, 'freightBillingStreetAddress');
                    $freightBillingStreetAddress2 = ArrayHelper::remove($settings, 'freightBillingStreetAddress2');
                    $freightBillingCity = ArrayHelper::remove($settings, 'freightBillingCity');
                    $freightBillingZipcode = ArrayHelper::remove($settings, 'freightBillingZipcode');
                    $freightBillingStateCode = ArrayHelper::remove($settings, 'freightBillingStateCode');
                    $freightBillingCountryCode = ArrayHelper::remove($settings, 'freightBillingCountryCode');
                    $freightShipperStreetAddress = ArrayHelper::remove($settings, 'freightShipperStreetAddress');
                    $freightShipperStreetAddress2 = ArrayHelper::remove($settings, 'freightShipperStreetAddress2');
                    $freightShipperCity = ArrayHelper::remove($settings, 'freightShipperCity');
                    $freightShipperZipcode = ArrayHelper::remove($settings, 'freightShipperZipcode');
                    $freightShipperStateCode = ArrayHelper::remove($settings, 'freightShipperStateCode');
                    $freightShipperCountryCode = ArrayHelper::remove($settings, 'freightShipperCountryCode');

                    // Create the freight provider as well, if required
                    if ($freight) {
                        $freightProvider = new providers\FedExFreight(array_merge([
                            'name' => $name . ' Freight',
                            'handle' => $handle . 'Freight',
                            'enabled' => $enabled,
                            'markUpRate' => $markUpRate,
                            'markUpBase' => $markUpBase,
                            'packingMethod' => $packingMethod,
                            'boxSizes' => $boxSizes,
                            'restrictServices' => $restrictServices,
                            'services' => $services,
                            'freightAccountNumber' => $freightAccountNumber,
                            'freightBillingStreetAddress' => $freightBillingStreetAddress,
                            'freightBillingStreetAddress2' => $freightBillingStreetAddress2,
                            'freightBillingCity' => $freightBillingCity,
                            'freightBillingZipcode' => $freightBillingZipcode,
                            'freightBillingStateCode' => $freightBillingStateCode,
                            'freightBillingCountryCode' => $freightBillingCountryCode,
                            'freightShipperStreetAddress' => $freightShipperStreetAddress,
                            'freightShipperStreetAddress2' => $freightShipperStreetAddress2,
                            'freightShipperCity' => $freightShipperCity,
                            'freightShipperZipcode' => $freightShipperZipcode,
                            'freightShipperStateCode' => $freightShipperStateCode,
                            'freightShipperCountryCode' => $freightShipperCountryCode,
                        ], $settings));

                        // Validate the provider and save. We have to remove everything from PC first before adding new stuff
                        Postie::$plugin->getProviders()->saveProvider($freightProvider);
                    }
                }

                if ($handle === 'newZealandPost') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                    ArrayHelper::remove($settings, 'siteCode');
                }

                if ($handle === 'sendle') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useSandbox');
                }

                if ($handle === 'ups') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                }

                if ($handle === 'upsLegacy') {
                    $settings['isProduction'] = !ArrayHelper::remove($settings, 'useTestEndpoint');
                    $settings['clientId'] = 'tbc';
                    $settings['clientSecret'] = 'tbc';
                    ArrayHelper::remove($settings, 'apiKey');
                    ArrayHelper::remove($settings, 'testApiKey');
                    ArrayHelper::remove($settings, 'username');
                    ArrayHelper::remove($settings, 'password');
                    ArrayHelper::remove($settings, 'negotiatedRates');
                    ArrayHelper::remove($settings, 'residentialAddress');

                    $freight = ArrayHelper::remove($settings, 'enableFreight');
                    $freightPackingType = ArrayHelper::remove($settings, 'freightPackingType');
                    $freightClass = ArrayHelper::remove($settings, 'freightClass');
                    $freightShipperName = ArrayHelper::remove($settings, 'freightShipperName');
                    $freightShipperEmail = ArrayHelper::remove($settings, 'freightShipperEmail');

                    // Create the freight provider as well, if required
                    if ($freight) {
                        $freightProvider = new providers\UPSFreight(array_merge([
                            'name' => $name . ' Freight',
                            'handle' => $handle . 'Freight',
                            'enabled' => $enabled,
                            'markUpRate' => $markUpRate,
                            'markUpBase' => $markUpBase,
                            'packingMethod' => $packingMethod,
                            'boxSizes' => $boxSizes,
                            'restrictServices' => $restrictServices,
                            'services' => $services,
                            'freightPackingType' => $freightPackingType,
                            'freightClass' => $freightClass,
                        ], $settings));

                        // Validate the provider and save. We have to remove everything from PC first before adding new stuff
                        Postie::$plugin->getProviders()->saveProvider($freightProvider);
                    }
                }

                if ($handle === 'usps') {
                    $settings['clientId'] = 'tbc';
                    $settings['clientSecret'] = 'tbc';
                    $settings['accountNumber'] = 'tbc';
                    ArrayHelper::remove($settings, 'username');
                }

                // We only care about enabled providers
                if (!$enabled) {
                    continue;
                }

                $class = $classMap[$handle] ?? null;

                // Custom providers aren't supported and need to be manually converted
                if (!$class) {
                    continue;
                }

                $newProvider = new $class(array_merge([
                    'name' => $name,
                    'handle' => $handle,
                    'enabled' => $enabled,
                    'markUpRate' => $markUpRate,
                    'markUpBase' => $markUpBase,
                    'packingMethod' => $packingMethod,
                    'boxSizes' => $boxSizes,
                    'restrictServices' => $restrictServices,
                    'services' => $services,
                ], $settings));

                // Validate the provider and save. We have to remove everything from PC first before adding new stuff
                if (!Postie::$plugin->getProviders()->saveProvider($newProvider)) {
                    echo 'Unable to migrate provider ' . $handle . ': ' . Json::encode($newProvider->getErrors());

                    return false;
                }
            }
        }

        // Clear out old provider data
        $projectConfig->remove('plugins.postie.settings.providers');

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230926_000000_providers_migrate cannot be reverted.\n";
        return false;
    }
}
