<?php
namespace verbb\postie\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\Db;
use craft\helpers\StringHelper;

class m190326_000000_craft_3 extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.postie.schemaVersion', true);

        if (version_compare($schemaVersion, '2.0.0', '>=')) {
            return true;
        }

        // Create the new tokens table
        if ($this->db->tableExists('{{%postie_address}}')) {
            Db::dropAllForeignKeysOnTable('{{%postie_address}}');
            Db::dropAllIndexesOnTable('{{%postie_address}}');

            $this->dropTableIfExists('{{%postie_address}}');
        }

        if ($this->db->tableExists('{{%postie_providers}}')) {
            $providers = (new Query())
                ->select(['*'])
                ->from(['{{%postie_providers}}'])
                ->all();

            foreach ($providers as $key => $provider) {
                $handle = StringHelper::toCamelCase($provider['handle']);
                $provider['settings'] = Json::decode($provider['settings']);

                unset($provider['id'], $provider['handle'], $provider['dateCreated'], $provider['dateUpdated'], $provider['uid']);

                $projectConfig->set('plugins.postie.settings.providers.' . $handle, $provider);
            }
        }

        if ($this->db->tableExists('{{%postie_shipping_methods}}')) {
            $methods = (new Query())
                ->select(['*'])
                ->from(['{{%postie_shipping_methods}}'])
                ->all();

            foreach ($methods as $method) {
                $provider = (new Query())
                    ->select(['*'])
                    ->from(['{{%postie_providers}}'])
                    ->where(['=', 'id', $method['providerId']])
                    ->one();

                $handle = StringHelper::toCamelCase($provider['handle']);

                $data = [
                    'name' => $method['name'],
                    'enabled' => $method['enabled'],
                ];

                $key = 'plugins.postie.settings.providers.' . $handle . '.services.' . $method['handle'];

                $projectConfig->set($key, $data);
            }
        }

        if ($this->db->tableExists('{{%postie_shipping_method_categories}}')) {
            $categories = (new Query())
                ->select(['*'])
                ->from(['{{%postie_shipping_method_categories}}'])
                ->all();

            foreach ($categories as $category) {
                $method = (new Query())
                    ->select(['*'])
                    ->from(['{{%postie_shipping_methods}}'])
                    ->where(['=', 'id', $category['shippingMethodId']])
                    ->one();

                $provider = (new Query())
                    ->select(['*'])
                    ->from(['{{%postie_providers}}'])
                    ->where(['=', 'id', $method['providerId']])
                    ->one();

                $handle = StringHelper::toCamelCase($provider['handle']);

                $data = [
                    'condition' => $category['condition'],
                ];

                $key = 'plugins.postie.settings.providers.' . $handle . '.services.' . $method['handle'] . '.shippingCategories.' . $category['shippingCategoryId'];

                $projectConfig->set($key, $data);
            }
        }

        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false));

        // Remove at the end, as items are all related
        if ($this->db->tableExists('{{%postie_providers}}')) {
            Db::dropAllForeignKeysOnTable('{{%postie_providers}}');
            Db::dropAllIndexesOnTable('{{%postie_providers}}');

            $this->dropTableIfExists('{{%postie_providers}}');
        }

        if ($this->db->tableExists('{{%postie_shipping_methods}}')) {
            Db::dropAllForeignKeysOnTable('{{%postie_shipping_methods}}');
            Db::dropAllIndexesOnTable('{{%postie_shipping_methods}}');

            $this->dropTableIfExists('{{%postie_shipping_methods}}');
        }

        if ($this->db->tableExists('{{%postie_shipping_method_categories}}')) {
            Db::dropAllForeignKeysOnTable('{{%postie_shipping_method_categories}}');
            Db::dropAllIndexesOnTable('{{%postie_shipping_method_categories}}');

            $this->dropTableIfExists('{{%postie_shipping_method_categories}}');
        }

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190326_000000_craft_3 cannot be reverted.\n";
        return false;
    }
}
