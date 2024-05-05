<?php
namespace verbb\postie\events;

use verbb\postie\models\Rate;

use yii\base\Event;

class RateEvent extends Event
{
    // Properties
    // =========================================================================

    public Rate $rate;
    public bool $isNew = false;
    
}
