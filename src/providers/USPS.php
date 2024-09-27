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

    public static function defineDefaultBoxes(): array
    {
        return [
            [
                'id' => 'usps-1',
                'name' => 'USPS Letter',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.25,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'enabled' => true,
            ],
            [
                'id' => 'usps-2',
                'name' => 'Priority Mail Flat Rate Small Box',
                'boxLength' => 8.6875,
                'boxWidth' => 5.4375,
                'boxHeight' => 1.75,
                'boxWeight' => 0.25,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-3',
                'name' => 'Priority Mail Flat Rate Medium Box',
                'boxLength' => 13.625,
                'boxWidth' => 11.875,
                'boxHeight' => 3.375,
                'boxWeight' => 0.625,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-4',
                'name' => 'Priority Mail Flat Rate Large Box',
                'boxLength' => 12.25,
                'boxWidth' => 12.25,
                'boxHeight' => 6,
                'boxWeight' => 1.25,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-5',
                'name' => 'Priority Mail Express Padded Flat Rate Envelope',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.5,
                'boxWeight' => 0.15,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-6',
                'name' => 'Priority Mail Regional Rate Box A',
                'boxLength' => 10.125,
                'boxWidth' => 7.125,
                'boxHeight' => 5,
                'boxWeight' => 0.5625,
                'maxWeight' => 15,
                'enabled' => true,
            ],
            [
                'id' => 'usps-7',
                'name' => 'Priority Mail Regional Rate Box B',
                'boxLength' => 16.25,
                'boxWidth' => 14.5,
                'boxHeight' => 3,
                'boxWeight' => 1.0625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'usps-8',
                'name' => 'Priority Mail Regional Rate Box C',
                'boxLength' => 15,
                'boxWidth' => 12,
                'boxHeight' => 12,
                'boxWeight' => 2.5,
                'maxWeight' => 25,
                'enabled' => true,
            ],
            [
                'id' => 'usps-9',
                'name' => 'Priority Mail Express® Legal Flat Rate Envelope',
                'boxLength' => 15,
                'boxWidth' => 9.5,
                'boxHeight' => 0.75,
                'boxWeight' => 0.20,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-10',
                'name' => 'Priority Mail Shoe Box',
                'boxLength' => 14.875,
                'boxWidth' => 7.375,
                'boxHeight' => 5.125,
                'boxWeight' => 0.6875,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-11',
                'name' => 'Priority Mail Large Video Box',
                'boxLength' => 9.25,
                'boxWidth' => 6.25,
                'boxHeight' => 2,
                'boxWeight' => 0.375,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-12',
                'name' => 'Priority Mail Small Tube Box',
                'boxLength' => 25.5,
                'boxWidth' => 6,
                'boxHeight' => 6,
                'boxWeight' => 1,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-13',
                'name' => 'Priority Mail Medium Cube-Shaped Box',
                'boxLength' => 12,
                'boxWidth' => 12,
                'boxHeight' => 8,
                'boxWeight' => 1,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-14',
                'name' => 'Priority Mail Express Medium Box',
                'boxLength' => 13.625,
                'boxWidth' => 11.875,
                'boxHeight' => 3.375,
                'boxWeight' => 1.0,
                'maxWeight' => 70,
                'enabled' => true,
            ],
            [
                'id' => 'usps-15',
                'name' => 'Priority Mail Medium Box 2',
                'boxLength' => 11,
                'boxWidth' => 8.5,
                'boxHeight' => 5.5,
                'boxWeight' => 0.5,
                'maxWeight' => 70,
                'enabled' => true,
            ],
        ];
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
