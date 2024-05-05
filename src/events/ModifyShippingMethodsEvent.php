<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyShippingMethodsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $order = null;
    public array $shippingMethods = [];
}
