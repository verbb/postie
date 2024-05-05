<?php
namespace verbb\postie\migrations;

use Craft;
use craft\db\Migration;

class m230816_000000_ups extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.postie.schemaVersion', true);

        // Craft::dd($projectConfig->get('plugins.postie'));
        if (version_compare($schemaVersion, '2.1.1', '>=')) {
            return true;
        }

        $providerGroups = $projectConfig->get('plugins.postie.settings.providers');

        if (is_array($providerGroups)) {
            foreach ($providerGroups as $providersKey => $providers) {
                foreach ($providers as $providerKey => $provider) {
                    if (isset($provider[0]) && $provider[0] === 'ups') {
                        $providerGroups[$providersKey][$providerKey][0] = 'upsLegacy';
                    }
                }
            }
        }

        $projectConfig->set('plugins.postie.settings.providers', $providerGroups);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230816_000000_ups cannot be reverted.\n";
        return false;
    }
}
