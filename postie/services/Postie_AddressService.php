<?php

namespace Craft;

class Postie_AddressService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    /**
     * Get shipping address model
     *
     * @return Postie_AddressModel
     */
    public function getAddress()
    {
        $record = Postie_AddressRecord::model()->findById(1);

        if (!$record) {
            $model = new Postie_AddressModel();
            $model->id = 1;

            return $model;
        }

        return Postie_AddressModel::populateModel($record);
    }

    /**
     * Save shipping address model in database
     *
     * @param Postie_AddressModel $model
     *
     * @return bool
     */
    public function saveAddress(Postie_AddressModel $model)
    {
        // Validate model
        if (!$model->validate()) {
            return false;
        }

        $record = Postie_AddressRecord::model()->findById($model->id);

        if (!$record) {
            $record = new Postie_AddressRecord();
        }

        // Set all provider record attributes
        $record->company = $model->company;
        $record->streetAddressLine1 = $model->streetAddressLine1;
        $record->streetAddressLine2 = $model->streetAddressLine2;
        $record->city = $model->city;
        $record->state = $model->state;
        $record->country = $model->country;
        $record->postalCode = $model->postalCode;

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