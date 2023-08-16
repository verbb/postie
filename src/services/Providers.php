<?php
namespace verbb\postie\services;

use verbb\postie\base\Provider;
use verbb\postie\base\ProviderInterface;
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\providers\AustraliaPost;
use verbb\postie\providers\Bring;
use verbb\postie\providers\CanadaPost;
use verbb\postie\providers\Colissimo;
use verbb\postie\providers\DHLExpress;
use verbb\postie\providers\Fastway;
use verbb\postie\providers\FedEx;
use verbb\postie\providers\Interparcel;
use verbb\postie\providers\NewZealandPost;
use verbb\postie\providers\PostNL;
use verbb\postie\providers\RoyalMail;
use verbb\postie\providers\Sendle;
use verbb\postie\providers\TNTAustralia;
use verbb\postie\providers\UPS;
use verbb\postie\providers\UPSLegacy;
use verbb\postie\providers\USPS;

use yii\base\Component;

class Providers extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_PROVIDER_TYPES = 'registerProviderTypes';


    // Public Methods
    // =========================================================================

    public function getAllProviders(): array
    {
        $providerTypes = $this->_getProviderTypes();

        $providers = [];

        foreach ($providerTypes as $providerType) {
            $provider = $this->_createProvider($providerType);

            $providers[$provider->handle] = $provider;
        }

        ksort($providers, SORT_STRING | SORT_FLAG_CASE);

        return $providers;
    }

    public function getProviderByHandle($handle): ?Provider
    {
        $providers = $this->getAllProviders();

        foreach ($providers as $provider) {
            if ($provider->handle == $handle) {
                return $provider;
            }
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    private function _getProviderTypes(): array
    {
        $providerTypes = [
            AustraliaPost::class,
            Bring::class,
            CanadaPost::class,
            Colissimo::class,
            DHLExpress::class,
            Fastway::class,
            FedEx::class,
            Interparcel::class,
            NewZealandPost::class,
            PostNL::class,
            RoyalMail::class,
            Sendle::class,
            // TNT::class,
            TNTAustralia::class,
            UPS::class,
            UPSLegacy::class,
            USPS::class,
        ];

        $event = new RegisterProviderTypesEvent([
            'providerTypes' => $providerTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_PROVIDER_TYPES, $event);

        return $event->providerTypes;
    }

    private function _createProvider($providerType): ProviderInterface
    {
        return new $providerType;
    }
}
