<?php

namespace Craft;

class m180122_012119_postie_ShippingMethodCategoryCondition extends BaseMigration
{
    /**
     * Create the craft_postie_shipping_method_categories table with indexes and foreign keys
     *
     * @return bool
     */
    public function safeUp()
    {
        // Create the craft_postie_shipping_method_categories table
        craft()->db->createCommand()->createTable('postie_shipping_method_categories', [
            'shippingMethodId'   => ['column' => 'integer', 'required' => false],
            'shippingCategoryId' => ['column' => 'integer', 'required' => false],
            'condition'          => ['values' => ['allow', 'disallow', 'require'], 'column' => 'enum', 'required' => true],
        ]);

        // Add indexes to craft_postie_shipping_method_categories
        craft()->db->createCommand()->createIndex('postie_shipping_method_categories', 'shippingMethodId');
        craft()->db->createCommand()->createIndex('postie_shipping_method_categories', 'shippingCategoryId');

        // Add foreign keys to craft_postie_shipping_method_categories
        craft()->db->createCommand()->addForeignKey('postie_shipping_method_categories', 'shippingMethodId', 'postie_shipping_methods', 'id', 'CASCADE');
        craft()->db->createCommand()->addForeignKey('postie_shipping_method_categories', 'shippingCategoryId', 'commerce_shippingcategories', 'id', 'CASCADE');

        return true;
    }
}
