<?php
namespace verbb\postie\events;

use verbb\postie\base\Provider;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Provider $provider = null;
    public mixed $payload = null;
    public ?Order $order = null;
}
