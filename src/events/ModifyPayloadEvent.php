<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class ModifyPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public $provider;
    public $payload;
    public $order;
}
