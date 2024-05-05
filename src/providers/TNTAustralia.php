<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\TNTAustralia as TNTAustraliaCarrier;

class TNTAustralia extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'TNT Australia');
    }

    public static function getCarrierClass(): string
    {
        return TNTAustraliaCarrier::class;
    }
    

    // Properties
    // =========================================================================

    public ?string $accountNumber = null;
    public ?string $username = null;
    public ?string $password = null;

    private int $maxDomesticWeight = 70000; // 70kg
    private int $maxInternationalWeight = 500000; // 500kg


    // Public Methods
    // =========================================================================

    public function getAccountNumber(): ?string
    {
        return App::parseEnv($this->accountNumber);
    }

    public function getUsername(): ?string
    {
        return App::parseEnv($this->username);
    }

    public function getPassword(): ?string
    {
        return App::parseEnv($this->password);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accountNumber', 'username', 'password'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['accountNumber'] = $this->getAccountNumber();
        $config['username'] = $this->getUsername();
        $config['password'] = $this->getPassword();

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
