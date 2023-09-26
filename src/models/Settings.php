<?php
namespace verbb\postie\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Postie';
    public bool $enableCaching = true;

}
