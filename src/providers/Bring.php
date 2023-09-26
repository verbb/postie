<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use verbb\shippy\carriers\Bring as BringCarrier;

class Bring extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Bring');
    }

    public static function getCarrierClass(): string
    {
        return BringCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $username = null;
    public ?string $apiKey = null;
    public ?string $customerNumber = null;


    // Public Methods
    // =========================================================================

    public function getUsername(): ?string
    {
        return App::parseEnv($this->username);
    }

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getCustomerNumber(): ?string
    {
        return App::parseEnv($this->customerNumber);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'apiKey'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        $rules[] = [['customerNumber'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['username'] = $this->getUsername();
        $config['apiKey'] = $this->getApiKey();
        $config['clientUrl'] = UrlHelper::siteUrl('/');

        if ($this->getApiType() === self::API_SHIPPING) {
            $config['customerNumber'] = $this->getCustomerNumber();
        }

        return $config;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('NO', ['locality' => 'Oslo']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('NO', ['locality' => 'Bergen']);
    }
}
