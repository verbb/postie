<?php
namespace verbb\postie\base;

use craft\base\SavableComponentInterface;

interface ProviderInterface extends SavableComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string;


    // Public Methods
    // =========================================================================

    public function fetchShippingRates($order): ?array;
}
