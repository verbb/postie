<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;

use Craft;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\FedEx as FedExCarrier;

class FedEx extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'FedEx');
    }

    public static function getCarrierClass(): string
    {
        return FedExCarrier::class;
    }

    public static function defineDefaultBoxes(): array
    {
        return [
            [
                'id' => 'fedex-1',
                'name' => 'FedEx® Small Box',
                'boxLength' => 12.375,
                'boxWidth' => 10.875,
                'boxHeight' => 1.5,
                'boxWeight' => 0.28125,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-2',
                'name' => 'FedEx® Small Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 2.625,
                'boxWeight' => 0.28125,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-3',
                'name' => 'FedEx® Medium Box',
                'boxLength' => 13.25,
                'boxWidth' => 11.5,
                'boxHeight' => 2.375,
                'boxWeight' => 0.40625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-4',
                'name' => 'FedEx® Medium Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 4.375,
                'boxWeight' => 0.40625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-5',
                'name' => 'FedEx® Large Box',
                'boxLength' => 17.5,
                'boxWidth' => 12.365,
                'boxHeight' => 3,
                'boxWeight' => 0.90625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-6',
                'name' => 'FedEx® Large Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 7.75,
                'boxWeight' => 0.5875,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-7',
                'name' => 'FedEx® Extra Large Box',
                'boxLength' => 11.875,
                'boxWidth' => 11,
                'boxHeight' => 10.75,
                'boxWeight' => 1.25,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-8',
                'name' => 'FedEx® Extra Large Box',
                'boxLength' => 15.75,
                'boxWidth' => 14.125,
                'boxHeight' => 6,
                'boxWeight' => 1.875,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-9',
                'name' => 'FedEx® Pak',
                'boxLength' => 15.5,
                'boxWidth' => 12,
                'boxHeight' => 1.5,
                'boxWeight' => 0.0625,
                'maxWeight' => 5.5,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-10',
                'name' => 'FedEx® Envelope',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.25,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-11',
                'name' => 'FedEx® 10kg Box',
                'boxLength' => 15.81,
                'boxWidth' => 12.94,
                'boxHeight' => 10.19,
                'boxWeight' => 1.9375,
                'maxWeight' => 22,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-12',
                'name' => 'FedEx® 25kg Box',
                'boxLength' => 21.56,
                'boxWidth' => 16.56,
                'boxHeight' => 13.19,
                'boxWeight' => 3.5625,
                'maxWeight' => 55,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-13',
                'name' => 'FedEx® Tube',
                'boxLength' => 38,
                'boxWidth' => 6,
                'boxHeight' => 6,
                'boxWeight' => 1,
                'maxWeight' => 20,
                'enabled' => true,
            ],
        ];
    }

    public static function getServiceList(): array
    {
        return array_merge(...array_values(parent::getServiceList()));
    }
    

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $accountNumber = null;

    private float $maxWeight = 68038.9; // 150lbs


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

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret', 'accountNumber'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['clientId'] = $this->getClientId();
        $config['clientSecret'] = $this->getClientSecret();
        $config['accountNumber'] = $this->getAccountNumber();

        return $config;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }
}
