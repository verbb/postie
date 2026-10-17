<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $rates = [];
    public mixed $response = [];
    public ?Order $order = null;
}
