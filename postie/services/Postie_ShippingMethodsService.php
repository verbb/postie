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
     * Get shipping methods by handle
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

        return true;
    }
}