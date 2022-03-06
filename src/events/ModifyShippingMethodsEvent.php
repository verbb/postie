<?php
namespace verbb\postie\events;

use verbb\postie\base\Provider;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyShippingMethodsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $order;
    public ?Provider $provider;
    public mixed $shippingMethods;
}
