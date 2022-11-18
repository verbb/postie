<?php
namespace verbb\postie\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class ModifyRatesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array|null A map of rate data, each element containing an `amount` key and an `options` key with Provider-specific API data.
     */
    public ?array $rates = [];

    /**
     * @var mixed The raw API response object from the Provider.
     */
    public mixed $response = [];

    /**
     * @var ?Order The order that was used when requesting rates.
     */
    public ?Order $order = null;
}
