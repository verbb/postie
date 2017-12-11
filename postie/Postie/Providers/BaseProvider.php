<?php

namespace Postie\Providers;

use function Craft\craft;
use Craft\Commerce_AddressRecord;
use Craft\Commerce_OrderModel;
use Craft\IOHelper;
use Craft\PostieHelper;

abstract class BaseProvider
{
    // Properties
    // =========================================================================

    public static $handle = 'Base';

    protected $_handle;
    protected $_name;
    protected $_services;
    protected $_originAddress;


    // Public Methods
    // =========================================================================

    /**
     * BaseProvider constructor.
     * Set settings from Database and merge it with values from the config file
     */
    public function __construct()
    {
        $configAddress = craft()->config->get('originAddress', 'postie');
        $settings = PostieHelper::getAddressService()->getAddress()->getAttributes();

        foreach ($settings as $paramKey => $paramValue) {
            if ($paramKey !== 'edition' && isset($configAddress[$paramKey])) {
                $settings[$paramKey] = $configAddress[$paramKey];
            }
        }

        $this->_originAddress = $settings;
    }

    /**
     * Get provider handle
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->_handle;
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get service list
     *
     * @return array
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * Get settings from Database and override it with values from config file
     *
     * @param $handle
     *
     * @return array|null
     */
    public function getProviderSettings($handle)
    {
        $settings = PostieHelper::getProvidersService()->getProviderModelByHandle($handle);

        if ($settings) {

            $providerSettings = craft()->config->get('providers', 'postie');
            $settingsArray = $settings->getAttributes();

            foreach ($settingsArray as $paramKey => $paramValue) {
                if (isset($providerSettings[$handle][$paramKey])) {
                    $settingsArray[$paramKey] = $providerSettings[$handle][$paramKey];
                }
            }

            // Merge enabled shipping methods from database with provider services from config file
            $settingsArray['services'] = [];
            foreach (PostieHelper::getShippingMethodsService()->getAllProviderShippingMethods($handle) as $service) {
                if (isset($providerSettings[$handle]['services'][$service->getHandle()])) {
                    $settingsArray['services'][$service->getHandle()] = $providerSettings[$handle]['services'][$service->getHandle()];
                } else if ($service->isEnabled()) {
                    $settingsArray['services'][$service->getHandle()] = $service->getName();
                }
            }

            return $settingsArray;
        }

        return null;
    }

    /**
     * Get shipping rate and stores in cache
     *
     * @param string              $handle
     * @param Commerce_OrderModel $order
     *
     * @return mixed
     */
    public function getShippingRate($handle, Commerce_OrderModel $order)
    {
        // Setup some caching mechanism to save API requests
        $signature = $this->_getSignature($handle, $order);
        $cacheKey = 'postie-shipment-' . $signature;

        // Get the rate from the cache (if any)
        $shippingRate = craft()->cache->get($cacheKey);

        // If is it not in the cache get rate via API
        if ($shippingRate === false) {
            $shippingRate = $this->createShipping($handle, $order);

            // Set this in our cache for the next request to be much quicker
            craft()->cache->set($cacheKey, $shippingRate, 0);
        }

        return $shippingRate;
    }


    // Protected Methods
    // =========================================================================

    /**
     * Get order signature for caching algorithm
     *
     * @param string              $handle
     * @param Commerce_OrderModel $order
     *
     * @return string
     */
    protected function _getSignature($handle, Commerce_OrderModel $order)
    {
        $totalQty = $order->getTotalQty();
        $totalWeight = $order->getTotalWeight();
        $totalWidth = $order->getTotalWidth();
        $totalHeight = $order->getTotalHeight();
        $totalLength = $order->getTotalLength();

        $shippingAddress = Commerce_AddressRecord::model()->findById($order->shippingAddressId);
        $shippingAddressDetails = '';

        if ($shippingAddress) {
            // use every single address detail instead of date updated because
            // the record gets updated every single time you select the address
            // in the frontend and creates a new signature even if the address
            // didn't change actually
            $shippingAddressDetails = $shippingAddress->attention;
            $shippingAddressDetails .= $shippingAddress->title;
            $shippingAddressDetails .= $shippingAddress->firstName;
            $shippingAddressDetails .= $shippingAddress->lastName;
            $shippingAddressDetails .= $shippingAddress->countryId;
            $shippingAddressDetails .= $shippingAddress->stateId;
            $shippingAddressDetails .= $shippingAddress->address1;
            $shippingAddressDetails .= $shippingAddress->address2;
            $shippingAddressDetails .= $shippingAddress->city;
            $shippingAddressDetails .= $shippingAddress->zipCode;
            $shippingAddressDetails .= $shippingAddress->phone;
            $shippingAddressDetails .= $shippingAddress->alternativePhone;
            $shippingAddressDetails .= $shippingAddress->businessName;
            $shippingAddressDetails .= $shippingAddress->businessTaxId;
            $shippingAddressDetails .= $shippingAddress->businessId;
            $shippingAddressDetails .= $shippingAddress->stateName;
        }

        return md5($handle . $totalQty . $totalWeight . $totalWidth . $totalHeight . $totalLength . $shippingAddressDetails);
    }

    /**
     * Box packing algorithm. Get the maximum width and length of all line items
     * and sum up the heights of all items
     *
     * @param Commerce_OrderModel $order
     *
     * @return array
     */
    protected function _getPackageDimensions(Commerce_OrderModel $order)
    {
        $maxWidth = 0;
        $maxLength = 0;

        foreach ($order->lineItems as $key => $lineItem) {
            $maxLength = $maxLength < $lineItem->length ? $maxLength = $lineItem->length : $maxLength;
            $maxWidth = $maxWidth < $lineItem->width ? $maxWidth = $lineItem->width : $maxWidth;
        }

        return [
            'length' => (int)$maxWidth,
            'width'  => (int)$maxLength,
            'height' => (int)$order->getTotalHeight(),
        ];
    }

    // Abstract Methods
    // =========================================================================

    /**
     * Abstract function to define the API setting fields.
     * Array must be defined by provider classes.
     *
     * @return array
     */
    abstract public function getAPIFields();

    /**
     * Abstract function to define the provider settings template.
     * Template path must be defined by provider classes.
     *
     * @return string
     */
    abstract public function getSettingsTemplate();

    /**
     * Abstract function to define the services. The list must be an array of key, value pairs.
     * The key defines the service handle, the value the service name.
     * Array must be defined by provider classes.
     *
     * @return array
     */
    abstract public function getServiceList();

    /**
     * Abstract function for shipping creation.
     * Logic must be implemented by provider classes.
     *
     * @param string              $handle
     * @param Commerce_OrderModel $order
     *
     * @return mixed
     */
    abstract public function createShipping($handle, Commerce_OrderModel $order);
}