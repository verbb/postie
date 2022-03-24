<?php
namespace verbb\postie\events;

use craft\elements\Address;

use craft\commerce\elements\Order;

use yii\base\Event;

class FetchRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Address $storeLocation = null;
    public ?Order $order = null;
    public mixed $packedBoxes = null;
}
