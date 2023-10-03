<?php
namespace verbb\postie\migrations;

use craft\db\Migration;

use craft\commerce\Plugin as Commerce;
use craft\commerce\models\OrderStatus;

class m230928_000000_shipments extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
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

        $this->createIndex(null, '{{%postie_shipments}}', 'orderId');
        $this->addForeignKey(null, '{{%postie_shipments}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%postie_shipments}}', 'providerHandle', '{{%postie_providers}}', 'handle', 'CASCADE', 'CASCADE');

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

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230928_000000_shipments cannot be reverted.\n";
        return false;
    }
}
