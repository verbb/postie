<?php
namespace verbb\postie\events;

use verbb\postie\base\Provider;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyShippingRuleEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Provider $provider;
    public mixed $shippingRule;
    public mixed $shippingMethod;

}
