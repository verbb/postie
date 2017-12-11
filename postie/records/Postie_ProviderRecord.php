<?php

namespace Craft;

/**
 * Class Postie_ProviderRecord
 *
 * @property integer id
 * @property string  handle
 * @property string  name
 * @property array   settings
 * @property string  markUpRate
 * @property array   markUpBase
 * @property boolean enabled
 */
class Postie_ProviderRecord extends BaseRecord
{
    // Properties
    // =========================================================================

    const PERCENTAGE = 'percentage';
    const VALUE = 'value';


    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'postie_providers';
    }

    /**
     * @return array
     */
    public static function getMarkUpBaseOptions()
    {
        return [
            self::PERCENTAGE,
            self::VALUE,
        ];
    }

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return [
            'handle'     => [AttributeType::String, 'required' => true],
            'name'       => [AttributeType::String, 'required' => true],
            'enabled'    => AttributeType::Bool,
            'settings'   => AttributeType::Mixed,
            'markUpRate' => [AttributeType::String,],
            'markUpBase' => [
                AttributeType::Enum,
                'required' => true,
                'values'   => self::getMarkUpBaseOptions(),
                'default'  => self::PERCENTAGE,
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