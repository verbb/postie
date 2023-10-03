<?php
namespace verbb\postie\variables;

use verbb\postie\Postie;
use verbb\postie\models\Rate;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class PostieVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): Postie
    {
        return Postie::$plugin;
    }

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

    public function getRateByOrder(Order $order): ?Rate
    {
        $rates = Postie::$plugin->getRates()->getRatesByOrderId($order->id);

        return array_pop($rates);
    }
}