<?php

namespace Craft;

/**
 * Class Postie_ShippingMethodModel
 *
 * @property integer id
 * @property integer shippingMethodId
 * @property integer shippingCategoryId
 * @property string  condition
 */
class Postie_ShippingMethodCategoryModel extends BaseModel
{
    // Properties
    // =========================================================================

    private $_shippingMethod;
    private $_shippingCategory;


    // Public Methods
    // =========================================================================

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return Postie_ShippingMethodModel
     */
    public function getShippingMethod()
    {
        if ($this->_shippingMethod) {
            return $this->_shippingMethod;
        }

        return $this->_shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodById($this->shippingMethodId);
    }

    public function getShippingCategory()
    {
        if ($this->_shippingCategory) {
            return $this->_shippingCategory;
        }

        return $this->_shippingCategory = craft()->commerce_shippingCategories->getShippingCategoryById($this->shippingCategoryId);
    }

    /**
     * Define Attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return [
            'id'                 => AttributeType::Number,
            'shippingMethodId'   => AttributeType::Number,
            'shippingCategoryId' => [AttributeType::Number],
            'condition'          => [
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