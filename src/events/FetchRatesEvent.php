<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class FetchRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $storeLocation = null;
    public ?Order $order = null;
    public mixed $packedBoxes = null;
    
    // Deprecated - todo remove at next breakpoint
    public mixed $dimensions = null;
}
