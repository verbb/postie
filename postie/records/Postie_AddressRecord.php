<?php

namespace Craft;

/**
 * Class Postie_ProviderRecord
 *
 * @property integer id
 * @property string  company
 * @property string  streetAddressLine1
 * @property string  streetAddressLine2
 * @property string  city
 * @property string  state
 * @property string  country
 * @property integer postalCode
 */
class Postie_AddressRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'postie_address';
    }


    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return [
            'company'            => [AttributeType::String, 'required' => false],
            'streetAddressLine1' => [AttributeType::String, 'required' => false],
            'streetAddressLine2' => [AttributeType::String, 'required' => false],
            'city'               => [AttributeType::String, 'required' => false],
            'postalCode'         => [AttributeType::String, 'required' => true],
            'state'              => [AttributeType::String, 'required' => false],
            'country'            => [AttributeType::String, 'required' => true],
        ];
    }
}