<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\AustraliaPost as AustraliaPostCarrier;

class AustraliaPost extends Provider
{
    // Constants
    // =========================================================================

    public const TYPE_BOX = 'box';
    public const TYPE_ENVELOPE = 'envelope';
    public const TYPE_PACKET = 'packet';
    public const TYPE_TUBE = 'tube';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Australia Post');
    }

    public static function getCarrierClass(): string
    {
        return AustraliaPostCarrier::class;
    }

    public static function defineDefaultBoxes(): array
    {
        return [
            [
                'id' => 'auspost-letter-1',
                'name' => 'DL 110 x 220',
                'boxLength' => 11,
                'boxWidth' => 22,
                'boxHeight' => 0.5,
                'boxWeight' => 0,
                'maxWeight' => 0.25,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-2',
                'name' => 'DL 130 x 240',
                'boxLength' => 13,
                'boxWidth' => 24,
                'boxHeight' => 0.5,
                'boxWeight' => 0,
                'maxWeight' => 0.25,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-3',
                'name' => 'C5 162 x 229',
                'boxLength' => 16.2,
                'boxWidth' => 22.9,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-4',
                'name' => 'C4 324 x 229',
                'boxLength' => 32.4,
                'boxWidth' => 22.9,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-5',
                'name' => 'B4 353 x 250',
                'boxLength' => 35.3,
                'boxWidth' => 25,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-1',
                'name' => 'Small Satchel',
                'boxLength' => 35.5,
                'boxWidth' => 22.5,
                'boxHeight' => 8,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-2',
                'name' => 'Medium Satchel',
                'boxLength' => 39,
                'boxWidth' => 27,
                'boxHeight' => 12,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-3',
                'name' => 'Large Satchel',
                'boxLength' => 41.5,
                'boxWidth' => 31.5,
                'boxHeight' => 14,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-4',
                'name' => 'Extra Large Satchel',
                'boxLength' => 44,
                'boxWidth' => 51,
                'boxHeight' => 15,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-1',
                'name' => 'Small Box',
                'boxLength' => 16,
                'boxWidth' => 22,
                'boxHeight' => 7,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-2',
                'name' => 'Medium Box',
                'boxLength' => 19,
                'boxWidth' => 24,
                'boxHeight' => 12,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-3',
                'name' => 'Large Box',
                'boxLength' => 28,
                'boxWidth' => 39,
                'boxHeight' => 14,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-4',
                'name' => 'Extra Large Box',
                'boxLength' => 27.7,
                'boxWidth' => 44,
                'boxHeight' => 16.8,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
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

    public ?string $apiKey = null;
    public ?string $password = null;
    public ?string $accountNumber = null;

    private int $maxDomesticWeight = 22000; // 22kg
    private int $maxInternationalWeight = 20000; // 20kg


    // Public Methods
    // =========================================================================

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getPassword(): ?string
    {
        return App::parseEnv($this->password);
    }

    public function getAccountNumber(): ?string
    {
        return App::parseEnv($this->accountNumber);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        $rules[] = [['password', 'accountNumber'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['apiKey'] = $this->getApiKey();

        if ($this->getApiType() === self::API_SHIPPING) {
            $config['password'] = $this->getPassword();
            $config['accountNumber'] = $this->getAccountNumber();
        }

        return $config;
    }

    public function getApiTypeOptions(): array
    {
        return [
            ['label' => Craft::t('postie', 'Postage Assessment Calculation (Rates Only)'), 'value' => self::API_RATES],
            ['label' => Craft::t('postie', 'Shipping and Tracking (All)'), 'value' => self::API_SHIPPING],
        ];
    }

    public function getBoxSizesSettings(): array
    {
        $sizes = parent::getBoxSizesSettings();

        $newCols = [
            'boxType' => [
                'type' => 'select',
                'heading' => Craft::t('postie', 'Type'),
                'thin' => true,
                'small' => true,
                'options' => [
                    ['label' => Craft::t('postie', 'Box'), 'value' => self::TYPE_BOX],
                    ['label' => Craft::t('postie', 'Envelope'), 'value' => self::TYPE_ENVELOPE],
                    ['label' => Craft::t('postie', 'Packet'), 'value' => self::TYPE_PACKET],
                    ['label' => Craft::t('postie', 'Tube'), 'value' => self::TYPE_TUBE],
                ],
            ],
        ];

        // Add the new column, but before the enabled lightswitch
        $index = array_search('enabled', array_keys($sizes));
        return array_merge(array_slice($sizes, 0, $index), $newCols, array_slice($sizes, $index));
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
