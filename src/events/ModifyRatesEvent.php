<?php
namespace verbb\postie\events;

use yii\base\Event;

class ModifyRatesEvent extends Event
{
    // Properties
    // =========================================================================

    public $rates = [];
    public $response = [];
}
