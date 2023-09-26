<?php
namespace verbb\postie\events;

use verbb\postie\base\ProviderInterface;

use yii\base\Event;

class ProviderEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ProviderInterface $provider = null;
    public bool $isNew = false;
    
}
