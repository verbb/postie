<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

use verbb\shippy\carriers\CarrierInterface;
use verbb\shippy\models\Request;

class FetchRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $order = null;
    public ?CarrierInterface $carrier = null;
    public ?Request $request = null;
    public array $response = [];
}
