<?php
namespace verbb\postie\models;

use verbb\postie\Postie;
use verbb\postie\base\ProviderInterface;

use craft\base\Model;

use DateTime;

use verbb\shippy\carriers\CarrierInterface;

class Shipment extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $orderId = null;
    public ?string $providerHandle = null;
    public ?string $trackingNumber = null;
    public array $lineItems = [];
    public array $labels = [];
    public mixed $response = null;
    public mixed $errors = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private ?ProviderInterface $_provider = null;


    // Public Methods
    // =========================================================================

    public function getProvider(): ?ProviderInterface
    {
        if ($this->_provider) {
            return $this->_provider;
        }

        if ($this->providerHandle) {
            return $this->_provider = Postie::$plugin->getProviders()->getProviderByHandle($this->providerHandle);
        }

        return null;
    }

    public function getCarrier(): ?CarrierInterface
    {
        if ($provider = $this->getProvider()) {
            return $provider->getCarrier();
        }

        return null;
    }

    public function getTrackingUrl(): ?string
    {
        if ($this->trackingNumber && ($provider = $this->getProvider())) {
            return $provider->getCarrier()->getTrackingUrl($this->trackingNumber);
        }

        return null;
    }

    public function getLabelId(): ?string
    {
        return $this->labels['id'] ?? null;
    }

}
