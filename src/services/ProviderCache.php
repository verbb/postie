<?php
namespace verbb\postie\services;

use craft\base\Component;

class ProviderCache extends Component
{
    // Properties
    // =========================================================================

    public array $rates = [];


    // Public Methods
    // =========================================================================

    public function getRates($key)
    {
        return $this->rates[$key] ?? null;
    }

    public function setRates($key, $value): void
    {
        $this->rates[$key] = $value;
    }

}
