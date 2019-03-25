<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\base\ProviderInterface;
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\providers\AustraliaPost;
use verbb\postie\providers\CanadaPost;
use verbb\postie\providers\Fastway;
use verbb\postie\providers\FedEx;
use verbb\postie\providers\TNT;
use verbb\postie\providers\UPS;
use verbb\postie\providers\USPS;

use Craft;

use yii\base\Component;

class Providers extends Component
{
    // Constants
    // =========================================================================

    const EVENT_REGISTER_PROVIDER_TYPES = 'registerProviderTypes';


    // Public Methods
    // =========================================================================

    public function getAllProviders(): array
    {
        $providerTypes = $this->_getProviderTypes();

        $providers = [];

        foreach ($providerTypes as $providerType) {
            $provider = $this->_createProvider($providerType);

            $providers[$provider->getHandle()] = $provider;
        }

        ksort($providers);

        return $providers;
    }

    public function getProvider($handle)
    {
        $providers = $this->getAllProviders();

        foreach ($providers as $provider) {
            if ($provider->getHandle() == $handle) {
                return $provider;
            }
        }
    }


    // Private Methods
    // =========================================================================

    private function _getProviderTypes(): array
    {
        $providerTypes = [
            AustraliaPost::class,
            CanadaPost::class,
            Fastway::class,
            FedEx::class,
            // TNT::class,
            UPS::class,
            USPS::class,
        ];

        $event = new RegisterProviderTypesEvent([
            'providerTypes' => $providerTypes
        ]);

        $this->trigger(self::EVENT_REGISTER_PROVIDER_TYPES, $event);

        return $event->providerTypes;
    }

    private function _createProvider($providerType): ProviderInterface
    {
        return new $providerType;
    }
}
