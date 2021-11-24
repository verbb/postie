<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Postie';
    public $hasCpSection = false;
    public $applyFreeShipping = false;
    public $enableCaching = true;
    public $displayDebug = false;
    public $displayErrors = false;
    public $displayFlashErrors = false;
    public $manualFetchRates = false;
    public $fetchRatesPostValue = 'postie-fetch-rates';
    public $providers = [];


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['providers'], 'validateProviders'];

        return $rules;
    }

    public function validateProviders()
    {
        foreach ($this->providers as $key => $provider) {
            if (!$provider['enabled']) {
                continue;
            }

            if ($provider['packingMethod'] === Provider::PACKING_BOX) {
                if (!$provider['boxSizes'] || !is_array($provider['boxSizes'])) {
                    $this->addError("providers.{$key}.settings.boxSizes", Craft::t('postie', 'You must provide at least one box.'));
                }

                if (is_array($provider['boxSizes'])) {
                    $enabledBoxes = ArrayHelper::where($provider['boxSizes'], 'enabled');

                    if (!$enabledBoxes) {
                        $this->addError("providers.{$key}.settings.boxSizes", Craft::t('postie', 'You must provide at least one enabled box.'));
                    }

                    foreach ($enabledBoxes as $k => $box) {
                        $name = $box['name'] ?? '';
                        $boxLength = $box['boxLength'] ?? '';
                        $boxWidth = $box['boxWidth'] ?? '';
                        $boxHeight = $box['boxHeight'] ?? '';
                        $boxWeight = $box['boxWeight'] ?? '';
                        $maxWeight = $box['maxWeight'] ?? '';
                        $enabled = $box['enabled'] ?? '';
                        $default = $box['default'] ?? '';

                        // Bypass validation if a default row - always just the enabled state
                        if ($default) {
                            continue;
                        }

                        if ($name === '' || $boxLength === '' || $boxWidth === '' || $boxHeight === '' || $boxWeight === '' || $maxWeight === '') {
                            $this->addError("providers.{$key}.settings.boxSizes", Craft::t('postie', 'You must provide values for all fields.'));

                            break;
                        }
                    }
                }
            }
        }
    }
}
