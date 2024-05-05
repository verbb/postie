<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Postie';
    public bool $hasCpSection = false;
    public bool $applyFreeShipping = false;
    public bool $enableCaching = true;
    public bool $displayDebug = false;
    public bool $displayErrors = false;
    public bool $displayFlashErrors = false;
    public bool $manualFetchRates = false;
    public string $fetchRatesPostValue = 'postie-fetch-rates';
    public array $providers = [];


    // Public Methods
    // =========================================================================

    public function validateProviders(): void
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['providers'], 'validateProviders'];

        return $rules;
    }
}
