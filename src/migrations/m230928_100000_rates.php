<?php
namespace verbb\postie\migrations;

use craft\db\Migration;

class m230928_100000_rates extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->archiveTableIfExists('{{%postie_rates}}');
        $this->createTable('{{%postie_rates}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'providerHandle' => $this->string(),
            'rate' => $this->string(),
            'service' => $this->string(),
            'response' => $this->text(),
            'errors' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%postie_rates}}', 'orderId');
        $this->addForeignKey(null, '{{%postie_rates}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%postie_rates}}', 'providerHandle', '{{%postie_providers}}', 'handle', 'CASCADE', 'CASCADE');

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230928_100000_rates cannot be reverted.\n";
        return false;
    }
}
