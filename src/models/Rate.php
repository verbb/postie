<?php
namespace verbb\postie\models;

use verbb\postie\Postie;
use verbb\postie\base\ProviderInterface;

use craft\base\Model;

use DateTime;

class Rate extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $orderId = null;
    public ?string $providerHandle = null;
    public ?string $rate = null;
    public ?string $service = null;
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

}
