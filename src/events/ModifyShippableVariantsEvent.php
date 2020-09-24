<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class ModifyShippableVariantsEvent extends Event
{
    // Properties
    // =========================================================================

    public $query;
}
