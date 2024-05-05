<?php
namespace verbb\postie\migrations;

use craft\db\Migration;

class m230813_000000_providers extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
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

        $this->createIndex(null, '{{%postie_providers}}', 'handle', true);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230813_000000_providers cannot be reverted.\n";
        return false;
    }
}
