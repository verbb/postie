<?php
namespace verbb\postie\base;

use craft\base\SavableComponentInterface;

interface ProviderInterface extends SavableComponentInterface
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string;
    public function fetchShippingRates($order);
}
