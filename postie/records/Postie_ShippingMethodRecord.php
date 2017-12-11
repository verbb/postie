<?php

namespace Craft;

/**
 * Class Postie_ShippingMethodRecord
 *
 * @property integer id
 * @property integer providerId
 * @property string  handle
 * @property string  name
 * @property boolean enabled
 */
class Postie_ShippingMethodRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'postie_shipping_methods';
    }

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return [
            'handle'  => [AttributeType::String, 'required' => true],
            'name'    => [AttributeType::String, 'required' => false],
            'enabled' => [AttributeType::Bool, 'required' => false],
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
            'provider' => [
                static::BELONGS_TO,
                'Postie_ProviderRecord',
                'onDelete' => static::CASCADE,
            ],
        ];
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['handle'], 'unique' => true],
        ];
    }
}