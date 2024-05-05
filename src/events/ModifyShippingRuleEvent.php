<?php
namespace verbb\postie\events;

use verbb\postie\base\ProviderInterface;

use yii\base\Event;

class ModifyShippingRuleEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ProviderInterface $provider;
    public mixed $shippingRule;
    public mixed $shippingMethod;

}
