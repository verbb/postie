<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\Fastway as FastwayCarrier;

class Fastway extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Fastway');
    }

    public static function getCarrierClass(): string
    {
        return FastwayCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;

    private int $maxWeight = 5000; // 5kg


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

        return $config;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
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
