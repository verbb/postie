<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class ModifyShippingMethodsEvent extends Event
{
    // Properties
    // =========================================================================

    public $order;
    public $provider;
    public $shippingMethods;
}
