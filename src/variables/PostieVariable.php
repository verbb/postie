<?php
namespace verbb\postie\variables;

use verbb\postie\Postie;

use Craft;
use craft\fields\Assets;
use craft\web\View;

use yii\base\Behavior;

class PostieVariable
{
    public function getPluginName()
    {
        return Postie::$plugin->getPluginName();
    }
}