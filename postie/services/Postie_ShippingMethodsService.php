<?php

namespace Craft;


class Postie_ShippingMethodsService extends BaseApplicationComponent
{
    /**
     * Get a list of all provider shipping methods via provider handle
     *
     * @param string $providerHandle
     *
     * @return Postie_ShippingMethodModel[]
     */
    public function getAllProviderShippingMethods($providerHandle)
    {
        $providerModel = PostieHelper::getProvidersService()->getProviderModelByHandle($providerHandle);
        $methodRecords = Postie_ShippingMethodRecord::model()->findAllByAttributes(
            ['providerId' => $providerModel->id]
        );

        $methodModels = [];
        if ($methodRecords) {
            foreach ($methodRecords as $methodRecord) {
                $methodModels[] = Postie_ShippingMethodModel::populateModel($methodRecord);
            }
        }

        return $methodModels;
    }

    /**
     * Get shipping method by id
     *
     * @param int $shippingMethodId
     *
     * @return Postie_ShippingMethodModel
     */
    public function getShippingMethodById($shippingMethodId)
    {
        $methodRecord = Postie_ShippingMethodRecord::model()->findByAttributes(['id' => $shippingMethodId]);

        if (!$methodRecord) {
            return new Postie_ShippingMethodModel();
        }

        return Postie_ShippingMethodModel::populateModel($methodRecord);
    }

    /**
     * Get shipping method by handle
     *
     * @param string $shippingMethodHandle
     *
     * @return Postie_ShippingMethodModel
     */
    public function getShippingMethodModelByHandle($shippingMethodHandle)
    {
        $methodRecord = Postie_ShippingMethodRecord::model()->findByAttributes(['handle' => $shippingMethodHandle]);

        if (!$methodRecord) {
            return new Postie_ShippingMethodModel();
        }

        return Postie_ShippingMethodModel::populateModel($methodRecord);
    }

    /**
     * Get shipping method categories by shipping method id
     *
     * @param int $shippingMethodId
     *
     * @return Postie_ShippingMethodCategoryModel[]
     */
    public function getShippingMethodCategoriesByMethodId($shippingMethodId)
    {
        $record = Postie_ShippingMethodCategoryRecord::model()->findAllByAttributes([
            'shippingMethodId' => $shippingMethodId,
        ]);

        return Postie_ShippingMethodCategoryModel::populateModels($record, 'shippingCategoryId');
    }

    /**
     * Save shipping methods in database
     *
     * @param Postie_ShippingMethodModel $shippingMethod
     *
     * @return bool
     */
    public function saveShippingMethod(Postie_ShippingMethodModel $shippingMethod)
    {
        // Validate model
        if (!$shippingMethod->validate()) {
            return false;
        }

        $shippingMethodRecord = Postie_ShippingMethodRecord::model()->findById($shippingMethod->id);

        if (!$shippingMethodRecord) {
            $shippingMethodRecord = new Postie_ShippingMethodRecord();
        }

        // Set all shipping methods record attributes
        $shippingMethodRecord->providerId = $shippingMethod->providerId;
        $shippingMethodRecord->handle = $shippingMethod->handle;
        $shippingMethodRecord->name = $shippingMethod->name;
        $shippingMethodRecord->enabled = $shippingMethod->enabled;

        // Validate record
        if (!$shippingMethodRecord->validate()) {
            $shippingMethod->addErrors($shippingMethodRecord->getErrors());

            return false;
        }

        // Save record
        if (!$shippingMethodRecord->save()) {

            return false;
        }

        // Save shipping method category conditions
        if ($shippingMethod->getShippingMethodCategories()) {

            // Now that we have a record ID, save it on the model
            $shippingMethod->id = $shippingMethodRecord->id;

            // Delete already existing categories
            PostieHelper::getShippingMethodCategoriesService()->deleteShippingMethodCategoriesByMethodId($shippingMethod->id);

            // Generate a shipping method category record for all categories regardless of data submitted
            foreach (craft()->commerce_shippingCategories->getAllShippingCategories() as $shippingCategory)
            {
                if(isset($shippingMethod->getShippingMethodCategories()[$shippingCategory->id])
                    && $methodCategory = $shippingMethod->getShippingMethodCategories()[$shippingCategory->id])
                {
                    $condition = $methodCategory->condition;
                } else {
                    $condition = Commerce_ShippingRuleCategoryRecord::CONDITION_ALLOW;
                }

                $shippingMethodCategory = new Postie_ShippingMethodCategoryModel();
                $shippingMethodCategory->shippingMethodId = $shippingMethod->id;
                $shippingMethodCategory->shippingCategoryId = $shippingCategory->id;
                $shippingMethodCategory->condition = $condition;

                // save shipping method category
                PostieHelper::getShippingMethodCategoriesService()->saveShippingMethodCategory($shippingMethodCategory);
            }
        }

        return true;
    }
}