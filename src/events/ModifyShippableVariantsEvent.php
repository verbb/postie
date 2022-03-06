<?php
namespace verbb\postie\events;

use craft\elements\db\ElementQueryInterface;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyShippableVariantsEvent extends Event
{
    // Properties
    // =========================================================================

    public ElementQueryInterface $query = null;
}
