<?php

namespace Craft;


class PostieVariable
{
    public function getPluginUrl()
    {
        return craft()->plugins->getPlugin('postie')->getPluginUrl();
    }

    public function getPluginName()
    {
        return craft()->plugins->getPlugin('postie')->getName();
    }

    public function getPluginVersion()
    {
        return craft()->plugins->getPlugin('postie')->getVersion();
    }

    public function isLicensed()
    {
        return PostieHelper::getLicenseService()->isLicensed();
    }

    public function getEdition()
    {
        return PostieHelper::getLicenseService()->getEdition();
    }

    public function getProviderMarkUpBaseOptions()
    {
        $options = [];

        foreach (PostieHelper::getProvidersService()->getMarkUpBaseOptions() as $option) {
            $options[$option] = Craft::t(ucfirst($option));
        }

        return $options;
    }
}