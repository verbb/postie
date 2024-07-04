<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\App;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

use verbb\shippy\carriers\UPS as UPSCarrier;
use verbb\shippy\events\RateEvent;

use Throwable;

class UPS extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'UPS');
    }

    public static function getCarrierClass(): string
    {
        return UPSCarrier::class;
    }

    public static function defineDefaultBoxes(): array
    {
        return [
            [
                'id' => 'ups-1',
                'name' => 'UPS Letter',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.25,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'enabled' => true,
            ],
            [
                'id' => 'ups-2',
                'name' => 'Tube',
                'boxLength' => 38,
                'boxWidth' => 6,
                'boxHeight' => 6,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-3',
                'name' => '10KG Box',
                'boxLength' => 16.5,
                'boxWidth' => 13.25,
                'boxHeight' => 10.75,
                'boxWeight' => 0,
                'maxWeight' => 22,
                'enabled' => true,
            ],
            [
                'id' => 'ups-4',
                'name' => '25KG Box',
                'boxLength' => 19.75,
                'boxWidth' => 17.75,
                'boxHeight' => 13.2,
                'boxWeight' => 0,
                'maxWeight' => 55,
                'enabled' => true,
            ],
            [
                'id' => 'ups-5',
                'name' => 'Small Express Box',
                'boxLength' => 13,
                'boxWidth' => 11,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-6',
                'name' => 'Medium Express Box',
                'boxLength' => 16,
                'boxWidth' => 11,
                'boxHeight' => 3,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-7',
                'name' => 'Large Express Box',
                'boxLength' => 18,
                'boxWidth' => 13,
                'boxHeight' => 3,
                'boxWeight' => 0,
                'maxWeight' => 30,
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
    public ?string $requireSignature = null;
    public ?string $pickupType = null;
    public ?string $phoneField = null;
    public bool $includeInsurance = false;

    private float $maxWeight = 68038.9; // 150lbs

    private array $pickupCode = [
        '01' => 'Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '19' => 'Letter Center',
        '20' => 'Air Service Center',
    ];


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

        $rules[] = [['clientId', 'clientSecret'], 'required', 'when' => function($model) {
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
        $config['requireSignature'] = $this->requireSignature;
        $config['pickupType'] = $this->pickupType;
        $config['includeInsurance'] = $this->includeInsurance;

        return $config;
    }

    public function getPickupTypeOptions(): array
    {
        $options = [];

        foreach ($this->pickupCode as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function getPhoneFieldOptions(): array
    {
        $options = [];

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(Order::class);

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $options[] = ['label' => $field->name, 'value' => $field->handle];
            }
        }

        return $options;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }

    public function beforeFetchRates(RateEvent $event): void
    {
        // Add in the phone number for the recipient, which isn't included in an order address, but is required for international rates
        if ($this->phoneField) {
            try {
                $cart = Commerce::getInstance()->getCarts()->getCart();
                $phoneValue = $cart->{$this->phoneField};

                if ($phoneValue) {
                    $payload = $event->getRequest()->getPayload();
                    $payload['json']['RateRequest']['Shipment']['ShipTo']['Phone']['Number'] = $phoneValue;;

                    $event->getRequest()->setPayload($payload);
                }
            } catch (Throwable $e) {

            }
        }
    }
}
