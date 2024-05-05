<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\USPS as USPSCarrier;

class USPS extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'USPS');
    }

    public static function getCarrierClass(): string
    {
        return USPSCarrier::class;
    }

    public static function getServiceList(): array
    {
        $storeLocation = Postie::getStoreShippingAddress();

        $allServices = parent::getServiceList();
        $services = $allServices[$storeLocation->countryCode] ?? $allServices['international'];

        return $services;
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $accountNumber = null;
    public ?string $customerRegistrationId = null;
    public ?string $mailerId = null;

    private float $maxDomesticWeight = 31751.5; // 20lbs
    private float $maxInternationalWeight = 9071.85;


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

    public function getAccountNumber(): ?string
    {
        return App::parseEnv($this->accountNumber);
    }

    public function getCustomerRegistrationId(): ?string
    {
        return App::parseEnv($this->customerRegistrationId);
    }

    public function getMailerId(): ?string
    {
        return App::parseEnv($this->mailerId);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        $rules[] = [['accountNumber'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() !== self::API_TRACKING;
        }];

        $rules[] = [['customerRegistrationId', 'mailerId'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['clientId'] = $this->getClientId();
        $config['clientSecret'] = $this->getClientSecret();

        if ($this->getApiType() !== self::API_TRACKING) {
            $config['accountNumber'] = $this->getAccountNumber();
        }
        
        if ($this->getApiType() === self::API_SHIPPING) {
            $config['customerRegistrationId'] = $this->getCustomerRegistrationId();
            $config['mailerId'] = $this->getMailerId();
        }

        return $config;
    }

    public function getApiTypeOptions(): array
    {
        return [
            ['label' => Craft::t('postie', 'Rates Only'), 'value' => self::API_RATES],
            ['label' => Craft::t('postie', 'Tracking Only'), 'value' => self::API_TRACKING],
            ['label' => Craft::t('postie', 'All'), 'value' => self::API_SHIPPING],
        ];
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
    }
}
