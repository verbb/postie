<?php

namespace Craft;

/**
 * Class Postie_ShippingMethodCategoryRecord
 *
 * @property integer id
 * @property integer shippingMethodId
 * @property integer shippingCategoryId
 * @property string  condition
 */
class Postie_ShippingMethodCategoryRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'postie_shipping_method_categories';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['shippingMethodId']],
            ['columns' => ['shippingCategoryId']],
        ];
    }

    /**
     * Define relationships with other tables
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'shippingMethod'   => [
                static::BELONGS_TO,
                'Postie_ShippingMethodRecord',
                'onDelete' => static::CASCADE,
            ],
            'shippingCategory' => [
                static::BELONGS_TO,
                'Commerce_ShippingCategoryRecord',
                'onDelete' => static::CASCADE,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'condition' => [
                AttributeType::Enum,
                'values'   => [
                    Commerce_ShippingRuleCategoryRecord::CONDITION_ALLOW,
                    Commerce_ShippingRuleCategoryRecord::CONDITION_DISALLOW,
                    Commerce_ShippingRuleCategoryRecord::CONDITION_REQUIRE,
                ],
                'required' => true,
            ],
        ];
    }
}