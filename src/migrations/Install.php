<?php
namespace verbb\postie\migrations;

use Craft;
use craft\db\Migration;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();

        return true;
    }

    public function safeDown(): bool
    {
        $this->removeTables();
        $this->dropProjectConfig();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%postie_providers}}');
        $this->createTable('{{%postie_providers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'enabled' => $this->boolean()->defaultValue(true),
            'isProduction' => $this->boolean()->defaultValue(false),
            'markUpRate' => $this->string(),
            'markUpBase' => $this->string(),
            'restrictServices' => $this->boolean(),
            'services' => $this->text(),
            'packingMethod' => $this->string(),
            'boxSizes' => $this->text(),
            'settings' => $this->text(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%postie_providers}}', 'handle', true);
    }

    public function removeTables(): void
    {
        $this->dropTableIfExists('{{%postie_providers}}');
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('postie');
    }
}
