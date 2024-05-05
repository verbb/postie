<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\Sendle as SendleCarrier;

class Sendle extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Sendle');
    }

    public static function getCarrierClass(): string
    {
        return SendleCarrier::class;
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $sendleId = null;

    private int $maxDomesticWeight = 25000; // 70lbs
    private float $maxInternationalWeight = 31751.5;


    // Public Methods
    // =========================================================================

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getSendleId(): ?string
    {
        return App::parseEnv($this->sendleId);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'sendleId'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['apiKey'] = $this->getApiKey();
        $config['sendleId'] = $this->getSendleId();

        return $config;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
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
