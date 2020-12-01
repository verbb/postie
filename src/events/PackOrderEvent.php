<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class PackOrderEvent extends Event
{
    // Properties
    // =========================================================================

    public $packer;
    public $packedBoxes;
    public $order;
}
