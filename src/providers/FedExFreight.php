<?php
namespace verbb\postie\providers;

use Craft;
use craft\helpers\App;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\FedExFreight as FedExFreightCarrier;

class FedExFreight extends FedEx
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'FedEx Freight');
    }

    public static function getCarrierClass(): string
    {
        return FedExFreightCarrier::class;
    }

    public static function defineDefaultBoxes(): array
    {
        return [];
    }
    

    // Properties
    // =========================================================================

    public ?string $freightAccountNumber = null;
    public ?string $freightBillingStreetAddress = null;
    public ?string $freightBillingStreetAddress2 = null;
    public ?string $freightBillingCity = null;
    public ?string $freightBillingZipcode = null;
    public ?string $freightBillingStateCode = null;
    public ?string $freightBillingCountryCode = null;
    public ?string $freightShipperStreetAddress = null;
    public ?string $freightShipperStreetAddress2 = null;
    public ?string $freightShipperCity = null;
    public ?string $freightShipperZipcode = null;
    public ?string $freightShipperStateCode = null;
    public ?string $freightShipperCountryCode = null;

    private float $maxWeight = 68038.9; // 150lbs


    // Public Methods
    // =========================================================================

    public function getFreightAccountNumber(): ?string
    {
        return App::parseEnv($this->freightAccountNumber);
    }

    public function getFreightBillingStreetAddress(): ?string
    {
        return App::parseEnv($this->freightBillingStreetAddress);
    }

    public function getFreightBillingStreetAddress2(): ?string
    {
        return App::parseEnv($this->freightBillingStreetAddress2);
    }

    public function getFreightBillingCity(): ?string
    {
        return App::parseEnv($this->freightBillingCity);
    }

    public function getFreightBillingZipcode(): ?string
    {
        return App::parseEnv($this->freightBillingZipcode);
    }

    public function getFreightBillingStateCode(): ?string
    {
        return App::parseEnv($this->freightBillingStateCode);
    }

    public function getFreightBillingCountryCode(): ?string
    {
        return App::parseEnv($this->freightBillingCountryCode);
    }

    public function getFreightShipperStreetAddress(): ?string
    {
        return App::parseEnv($this->freightShipperStreetAddress);
    }

    public function getFreightShipperStreetAddress2(): ?string
    {
        return App::parseEnv($this->freightShipperStreetAddress2);
    }

    public function getFreightShipperCity(): ?string
    {
        return App::parseEnv($this->freightShipperCity);
    }

    public function getFreightShipperZipcode(): ?string
    {
        return App::parseEnv($this->freightShipperZipcode);
    }

    public function getFreightShipperStateCode(): ?string
    {
        return App::parseEnv($this->freightShipperStateCode);
    }

    public function getFreightShipperCountryCode(): ?string
    {
        return App::parseEnv($this->freightShipperCountryCode);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['freightAccountNumber', 'freightBillingStreetAddress', 'freightBillingStreetAddress2', 'freightBillingCity', 'freightBillingZipcode', 'freightBillingStateCode', 'freightBillingCountryCode', 'freightShipperStreetAddress', 'freightShipperStreetAddress2', 'freightShipperCity', 'freightShipperZipcode', 'freightShipperStateCode', 'freightShipperCountryCode'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['freightAccountNumber'] = $this->getFreightAccountNumber();
        $config['freightBillingStreetAddress'] = $this->getFreightBillingStreetAddress();
        $config['freightBillingStreetAddress2'] = $this->getFreightBillingStreetAddress2();
        $config['freightBillingCity'] = $this->getFreightBillingCity();
        $config['freightBillingZipcode'] = $this->getFreightBillingZipcode();
        $config['freightBillingStateCode'] = $this->getFreightBillingStateCode();
        $config['freightBillingCountryCode'] = $this->getFreightBillingCountryCode();
        $config['freightShipperStreetAddress'] = $this->getFreightShipperStreetAddress();
        $config['freightShipperStreetAddress2'] = $this->getFreightShipperStreetAddress2();
        $config['freightShipperCity'] = $this->getFreightShipperCity();
        $config['freightShipperZipcode'] = $this->getFreightShipperZipcode();
        $config['freightShipperStateCode'] = $this->getFreightShipperStateCode();
        $config['freightShipperCountryCode'] = $this->getFreightShipperCountryCode();

        return $config;
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }
}
