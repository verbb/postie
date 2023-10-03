<?php
namespace verbb\postie\behaviors;

use verbb\postie\Postie;

use craft\commerce\elements\Order;

use yii\base\Behavior;

class OrderShipmentsBehavior extends Behavior
{
    // Properties
    // =========================================================================

    private ?array $_shipments = null;


    // Public Methods
    // =========================================================================

    public function getShipments(): ?array
    {
        if (!$this->_shipments) {
            /* @var Order $order */
            $order = $this->owner;

            // Ensure the order is saved.
            if (!$order->id) {
                return null;
            }

            $this->_shipments = Postie::$plugin->getShipments()->getShipmentsByOrderId($order->id);
        }

        return $this->_shipments;
    }
}