<?php
namespace verbb\postie\events;

use yii\base\Event;

use craft\commerce\elements\Order;

class ModifyRatesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array A map of rate data, each element containing an `amount` key and an `options` key with Provider-specific API data.
     */
    public $rates = [];

    /**
     * @var array The raw API response object from the Provider.
     */
    public $response = [];

    /**
     * @var Order The order that was used when requesting rates.
     */
    public $order;
}
