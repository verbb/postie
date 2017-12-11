<?php

namespace Craft;


class Postie_ProvidersService extends BaseApplicationComponent
{
    /**
     * Get provider model by ID
     *
     * @param integer $providerId
     *
     * @return Postie_ProviderModel|null
     */
    public function getProviderById($providerId)
    {
        $providerRecord = Postie_ProviderRecord::model()->findById($providerId);

        if ($providerRecord) {
            return Postie_ProviderModel::populateModel($providerRecord);
        }

        return null;
    }

    /**
     * Get provider model by handle
     * If no database record exists, create a new provider model and populate
     * attributes with default values handle and name
     *
     * @param string $providerHandle
     *
     * @return Postie_ProviderModel
     */
    public function getProviderModelByHandle($providerHandle)
    {
        $providerRecord = Postie_ProviderRecord::model()->findByAttributes(['handle' => $providerHandle]);

        if ($providerRecord) {
            $providerModel = Postie_ProviderModel::populateModel($providerRecord);
        } else {
            $attributes = [];
            $attributes['handle'] = $providerHandle;

            // Parse provider name. Special case for USPS and FedEx
            if ($providerHandle == 'USPS') {
                $providerName = $providerHandle;
            } else if ($providerHandle == 'fedEx') {
                $providerName = 'FedEx';
            } else {
                $providerName = ucwords(preg_replace('/([A-Z])/', ' $1', $providerHandle));
            }
            $attributes['name'] = $providerName;

            $providerModel = Postie_ProviderModel::populateModel($attributes);
        }

        return $providerModel;
    }

    /**
     * Get Provider Mark-up options
     *
     * @return array
     */
    public function getMarkUpBaseOptions()
    {
        return Postie_ProviderRecord::getMarkUpBaseOptions();
    }

    /**
     * Save provider model in database via provider record
     *
     * @param Postie_ProviderModel $provider
     *
     * @return bool
     */
    public function saveProvider(Postie_ProviderModel $provider)
    {
        // Validate model
        if (!$provider->validate()) {
            return false;
        }

        $providerRecord = Postie_ProviderRecord::model()->findById($provider->id);

        if (!$providerRecord) {
            $providerRecord = new Postie_ProviderRecord();
        }

        // Set all provider record attributes
        $providerRecord->handle = $provider->handle;
        $providerRecord->name = $provider->name;
        $providerRecord->enabled = $provider->enabled;
        $providerRecord->settings = $provider->settings;
        $providerRecord->markUpRate = $provider->markUpRate;
        $providerRecord->markUpBase = $provider->markUpBase;
        $providerRecord->enabled = $provider->enabled;

        // Validate record
        if (!$providerRecord->validate()) {
            $provider->addErrors($providerRecord->getErrors());

            return false;
        }

        // Save record
        $result = $providerRecord->save();

        if (!$provider->id) {
            $provider->id = $providerRecord->id;
        }

        return $result;
    }
}