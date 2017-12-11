<?php

namespace Craft;

/**
 * Class PostieHelper
 * Creates helper functions for plugin/services to provide code completion
 *
 * @package Craft
 */
class PostieHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Get instance of PostiePlugin
     *
     * @return PostiePlugin
     */
    public static function getPlugin()
    {
        return craft()->plugins->getPlugin('postie');
    }

    /**
     * Get instance of PostieService
     *
     * @return PostieService
     */
    public static function getService()
    {
        return craft()->postie;
    }

    /**
     * Get instance of Postie_ProvidersService
     *
     * @return Postie_ProvidersService
     */
    public static function getProvidersService()
    {
        return craft()->postie_providers;
    }

    /**
     * Get instance of Postie_ShippingMethodsService
     *
     * @return Postie_ShippingMethodsService
     */
    public static function getShippingMethodsService()
    {
        return craft()->postie_shippingMethods;
    }

    /**
     * Get instance of Postie_LicenseService
     *
     * @return Postie_LicenseService
     */
    public static function getLicenseService()
    {
        return craft()->postie_license;
    }

    /**
     * Get instance of Postie_AddressService
     *
     * @return Postie_AddressService
     */
    public static function getAddressService()
    {
        return craft()->postie_address;
    }
}