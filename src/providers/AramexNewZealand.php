<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use verbb\shippy\carriers\AramexNewZealand as AramexNewZealandCarrier;

class AramexNewZealand extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Aramex New Zealand');
    }

    public static function getCarrierClass(): string
    {
        return AramexNewZealandCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;


    // Public Methods
    // =========================================================================

    public function getClientId(): ?string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): ?string
    {
        return App::parseEnv($this->clientSecret);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['clientId'] = $this->getClientId();
        $config['clientSecret'] = $this->getClientSecret();

        return $config;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('NZ', ['locality' => 'Wellington']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('NZ', ['locality' => 'Christchurch']);
    }
}
