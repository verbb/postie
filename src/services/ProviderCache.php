<?php
namespace verbb\postie\services;

use verbb\postie\Postie;

use Craft;
use craft\base\Component;
use craft\db\Query;

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
