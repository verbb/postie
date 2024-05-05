<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use verbb\shippy\carriers\Interparcel as InterparcelCarrier;

class Interparcel extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Interparcel');
    }

    public static function getCarrierClass(): string
    {
        return InterparcelCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public array $carriers = [];
    public array $serviceLevels = [];
    public array $pickupTypes = [];


    // Public Methods
    // =========================================================================

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['apiKey'] = $this->getApiKey();
        $config['carriers'] = $this->carriers;
        $config['serviceLevels'] = $this->serviceLevels;
        $config['pickupTypes'] = $this->pickupTypes;

        return $config;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS']);
    }

}
