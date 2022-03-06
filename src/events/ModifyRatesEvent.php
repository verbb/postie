<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyRatesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array A map of rate data, each element containing an `amount` key and an `options` key with Provider-specific API data.
     */
    public array $rates = [];

    /**
     * @var array The raw API response object from the Provider.
     */
    public array $response = [];

    /**
     * @var ?Order The order that was used when requesting rates.
     */
    public ?Order $order = null;
}
