<?php
namespace verbb\postie\events;

use verbb\postie\models\Shipment;

use yii\base\Event;

class ShipmentEvent extends Event
{
    // Properties
    // =========================================================================

    public Shipment $shipment;
    public bool $isNew = false;
    
}
