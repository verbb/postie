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

    public function fields(): array
    {
        $fields = parent::fields();

        // Keep the runtime objects used for pricing out of public array and JSON representations.
        unset($fields['provider'], $fields['shippingMethod']);

        $fields['providerHandle'] = fn() => $this->provider?->handle;
        $fields['providerName'] = fn() => $this->provider?->name;

        return $fields;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getBaseRate(): float
    {
        if ($this->baseRate && $this->provider->getMarkUpRate() != '') {
            if ($this->provider->getMarkUpBase() == Provider::VALUE) {
                $this->baseRate += (float)$this->provider->getMarkUpRate();
            }

            if ($this->provider->getMarkUpBase() == Provider::PERCENTAGE) {
                $this->baseRate += $this->baseRate * (float)$this->provider->getMarkUpRate() / 100;
            }
        }

        return (float)$this->baseRate;
    }

    public function getShippingRuleCategories(): array
    {
        return $this->shippingMethod->shippingMethodCategories ?? [];
    }
}
