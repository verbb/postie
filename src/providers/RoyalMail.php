<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use verbb\shippy\carriers\RoyalMail as RoyalMailCarrier;

class RoyalMail extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Royal Mail');
    }

    public static function getCarrierClass(): string
    {
        return RoyalMailCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $clickAndDropApiKey = null;
    public bool $acceptTerms = true;
    public bool $checkCompensation = true;
    public bool $includeVat = true;
    public bool $useClickAndDropLabels = false;


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
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['checkCompensation'] = $this->checkCompensation;
        $config['includeVat'] = $this->includeVat;

        return $config;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('GB', ['locality' => 'London']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('GB', ['locality' => 'Glasgow']);
    }

}
