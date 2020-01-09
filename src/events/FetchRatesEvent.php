<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class FetchRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public $storeLocation;
    public $dimensions;
    public $order;
}
