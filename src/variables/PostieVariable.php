<?php
namespace verbb\postie\variables;

use verbb\postie\Postie;

use craft\commerce\Plugin as Commerce;

class PostieVariable
{
    // Public Methods
    // =========================================================================

    public function getPluginName(): string
    {
        return Postie::$plugin->getPluginName();
    }

    public function getGeneralBadge(): ?string
    {
        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        return $storeLocation ? '1' : null;
    }

    public function getProductsBadge(): ?string
    {
        return count(Postie::$plugin->getInvalidVariants());
    }

    public function getTrackingStatus(string $handle, array $trackingNumbers): array
    {
        return Postie::$plugin->getProviders()->getTrackingStatus($handle, $trackingNumbers);
    }
}