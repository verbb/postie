<?php

namespace Craft;


class Postie_ShippingMethodCategoriesService extends BaseApplicationComponent
{
    /**
     * Delete all shipping method categories by method id
     *
     * @param int $shippingMethodId
     *
     * @return int
     */
    public function deleteShippingMethodCategoriesByMethodId($shippingMethodId)
    {
        return Postie_ShippingMethodCategoryRecord::model()->deleteAllByAttributes([
            'shippingMethodId' => $shippingMethodId,
        ]);
    }

    /**
     * Save shipping methods category in database
     *
     * @param Postie_ShippingMethodCategoryModel $model
     *
     * @return bool
     */
    public function saveShippingMethodCategory(Postie_ShippingMethodCategoryModel $model)
    {
        // Validate model
        if (!$model->validate()) {
            return false;
        }

        $record = Postie_ShippingMethodCategoryRecord::model()->findById($model->id);

        if (!$record) {
            $record = new Postie_ShippingMethodCategoryRecord();
        }

        // Set all shipping method category record attributes
        $record->shippingMethodId = $model->shippingMethodId;
        $record->shippingCategoryId = $model->shippingCategoryId;
        $record->condition = $model->condition;

        // Validate record
        if (!$record->validate()) {
            $model->addErrors($record->getErrors());

            return false;
        }

        // Save record
        if (!$record->save()) {

            return false;
        }

        return true;
    }
}