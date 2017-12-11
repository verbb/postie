<?php

namespace Craft;

/**
 * Class Postie_ShippingAddressModel
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
class Postie_AddressModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    /**
     * Define Attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return [
            'id'                 => AttributeType::Number,
            'company'            => [AttributeType::String, 'required' => false],
            'streetAddressLine1' => [AttributeType::String, 'required' => false],
            'streetAddressLine2' => [AttributeType::String, 'required' => false],
            'city'               => [AttributeType::String, 'required' => false],
            'postalCode'         => [AttributeType::String, 'required' => true],
            'state'              => [AttributeType::String, 'required' => false],
            'country'            => [AttributeType::String, 'required' => true],
        ];
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['postalCode', 'validatePostalCode'];
        $rules[] = ['state', 'validateState'];
        $rules[] = ['country', 'validateCountry'];

        return $rules;
    }

    public function validateCountry($attribute)
    {
        $value = $this->$attribute;

        if ($value == '0') {
            $this->addError($attribute, Craft::t('Please select a country.'));
        }
    }

    public function validateState($attribute)
    {
        $value = $this->$attribute;

        if ($value !== '' && (strlen($value) > 3 || is_numeric($value))) {
            $this->addError($attribute, Craft::t('Please enter a valid state abbreviation.'));
        }
    }

    public function validatePostalCode($attribute)
    {
        $value = $this->$attribute;

        if ($value !== '' && !is_numeric($value)) {
            $this->addError($attribute, Craft::t('Please enter a valid postal code.'));
        }
    }
}