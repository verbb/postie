<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\CanadaPost as CanadaPostCarrier;

class CanadaPost extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Canada Post');
    }

    public static function getCarrierClass(): string
    {
        return CanadaPostCarrier::class;
    }

    public static function getServiceList(): array
    {
        return array_merge(...array_values(parent::getServiceList()));
    }


    // Properties
    // =========================================================================

    public ?string $customerNumber = null;
    public ?string $contractId = null;
    public ?string $username = null;
    public ?string $password = null;
    public array $additionalOptions = [];

    private int $maxWeight = 30000; // 30kg


    // Public Methods
    // =========================================================================

    public function getCustomerNumber(): ?string
    {
        return App::parseEnv($this->customerNumber);
    }

    public function getContractId(): ?string
    {
        return App::parseEnv($this->contractId);
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

        $rules[] = [['customerNumber', 'username', 'password'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        $rules[] = [['contractId'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['customerNumber'] = $this->getCustomerNumber();
        $config['username'] = $this->getUsername();
        $config['password'] = $this->getPassword();
        $config['additionalOptions'] = $this->additionalOptions;

        if ($this->getApiType() === self::API_SHIPPING) {
            $config['contractId'] = $this->getContractId();
        }

        return $config;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('CA', ['locality' => 'Toronto']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('CA', ['locality' => 'Montreal']);
    }
}
