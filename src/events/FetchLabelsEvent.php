<?php
namespace verbb\postie\events;

use yii\base\Event;

use verbb\shippy\models\Request;

class FetchLabelsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Request $request = null;
    public array $response = [];
}
