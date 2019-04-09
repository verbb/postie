<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\Model;

use craft\commerce\base\ShippingRuleInterface;
use craft\commerce\models\ShippingRule as BaseShippingRule;

class ShippingRule extends BaseShippingRule
{
    // Properties
    // =========================================================================

    public $provider;
    public $shippingMethod;


    // Public Methods
    // =========================================================================

    public function getOptions(): array
    {
        return [];
    }

    public function getBaseRate(): float
    {
        if (isset($this->provider->markUpRate) && $this->provider->markUpRate != '') {
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
