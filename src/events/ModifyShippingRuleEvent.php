<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class ModifyShippingRuleEvent extends Event
{
    // Properties
    // =========================================================================

    public $provider;
    public $shippingRule;
    public $shippingMethod;

}
