<?php
namespace verbb\postie\migrations;

use Craft;
use craft\db\Migration;

use craft\commerce\Plugin as Commerce;
use craft\commerce\models\OrderStatus;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
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

        $this->archiveTableIfExists('{{%postie_shipments}}');
        $this->createTable('{{%postie_shipments}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'trackingNumber' => $this->string(),
            'providerHandle' => $this->string(),
            'lineItems' => $this->text(),
            'labels' => $this->longText(),
            'response' => $this->text(),
            'errors' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

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
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%postie_providers}}', 'handle', true);

        $this->createIndex(null, '{{%postie_shipments}}', 'orderId');
        $this->createIndex(null, '{{%postie_shipments}}', 'providerHandle');

        $this->createIndex(null, '{{%postie_rates}}', 'orderId');
        $this->createIndex(null, '{{%postie_rates}}', 'providerHandle');
    }

    protected function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%postie_shipments}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%postie_shipments}}', 'providerHandle', '{{%postie_providers}}', 'handle', 'CASCADE', 'CASCADE');

        $this->addForeignKey(null, '{{%postie_rates}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%postie_rates}}', 'providerHandle', '{{%postie_providers}}', 'handle', 'CASCADE', 'CASCADE');
    }

    public function insertDefaultData(): void
    {
        $orderStatusService = Commerce::getInstance()->getOrderStatuses();
        
        $statuses = [
            new OrderStatus([
                'name' => 'Shipped',
                'handle' => 'shipped',
                'color' => 'blue',
                'default' => false,
            ]),
            new OrderStatus([
                'name' => 'Partially Shipped',
                'handle' => 'partiallyShipped',
                'color' => 'yellow',
                'default' => false
            ]),
        ];

        foreach ($statuses as $status) {
            if (!$orderStatusService->getOrderStatusByHandle($status->handle)) {
                $orderStatusService->saveOrderStatus($status, []);
            }
        }
    }

    public function removeTables(): void
    {
        $this->dropTableIfExists('{{%postie_providers}}');
        $this->dropTableIfExists('{{%postie_shipments}}');
        $this->dropTableIfExists('{{%postie_rates}}');
    }

    protected function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%postie_providers}}')) {
            $this->dropAllForeignKeysToTable('{{%postie_providers}}');
        }

        if ($this->db->tableExists('{{%postie_shipments}}')) {
            $this->dropAllForeignKeysToTable('{{%postie_shipments}}');
        }

        if ($this->db->tableExists('{{%postie_rates}}')) {
            $this->dropAllForeignKeysToTable('{{%postie_rates}}');
        }
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('postie');
    }
}
