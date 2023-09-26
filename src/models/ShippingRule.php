<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;
use verbb\postie\base\ProviderInterface;

use craft\commerce\models\ShippingRule as BaseShippingRule;

class ShippingRule extends BaseShippingRule
{
    // Properties
    // =========================================================================

    public ?ProviderInterface $provider = null;
    public mixed $shippingMethod = null;
    public array $options = [];


    // Public Methods
    // =========================================================================

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getBaseRate(): float
    {
        if ($this->baseRate && isset($this->provider->markUpRate) && $this->provider->markUpRate != '') {
            if ($this->provider->markUpBase == Provider::VALUE) {
                $this->baseRate += (float)$this->provider->markUpRate;
            }

            if ($this->provider->markUpBase == Provider::PERCENTAGE) {
                $this->baseRate += $this->baseRate * (float)$this->provider->markUpRate / 100;
            }
        }

        return (float)$this->baseRate;
    }

    public function getShippingRuleCategories(): array
    {
        return $this->shippingMethod->shippingMethodCategories ?? [];
    }
}
