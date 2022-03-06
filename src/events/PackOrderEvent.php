<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class PackOrderEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $packer = null;
    public mixed $packedBoxes = null;
    public ?Order $order = null;
}
