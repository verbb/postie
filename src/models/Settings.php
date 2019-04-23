<?php
namespace verbb\postie\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Postie';
    public $hasCpSection = false;
    public $enableCaching = true;
    public $displayDebug = false;
    public $displayErrors = false;
    public $delayFetchRates = false;
    public $fetchRatesPostValue = 'postie-fetch-rates';
    public $providers = [];

}
