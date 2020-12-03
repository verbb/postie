<?php
namespace verbb\postie\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig as ProjectConfigHelper;

class m201201_000000_new_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.postie.schemaVersion', true);
        
        if (version_compare($schemaVersion, '2.1.0', '>=')) {
            return;
        }

        $providers = $projectConfig->get('plugins.postie.settings.providers');

        if ($providers) {
            $providers = ProjectConfigHelper::unpackAssociativeArray($providers, true);

            foreach ($providers as $key => $provider) {
                $provider['settings']['restrictServices'] = true;
                $provider['settings']['packingMethod'] = 'singleBox';

                $projectConfig->set("plugins.postie.settings.providers.{$key}", $provider);
            }
        }
    }

    public function safeDown()
    {
        echo "m201201_000000_new_settings cannot be reverted.\n";
        return false;
    }
}
