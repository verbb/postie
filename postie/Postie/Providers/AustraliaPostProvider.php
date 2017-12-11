<?php

namespace Postie\Providers;

use function Craft\craft;
use Craft\Commerce_OrderModel;
use Craft\LogLevel;
use Craft\PostieHelper;
use Craft\PostiePlugin;
use Auspost\Common\Auspost;
use Guzzle\Http\Exception\BadResponseException;

class AustraliaPostProvider extends BaseProvider
{
    // Properties
    // =========================================================================

    public static $handle = 'australiaPost';

    private $_countryList;
    private $_client;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        parent::__construct();

        $this->_handle = self::$handle;

        $settings = $this->getProviderSettings(self::$handle);
        $this->_name = $settings['name'];
        $this->_services = $settings['services'];

        // instantiate AusPost api client
        if (isset($settings['settings']['apiKey'])) {
            $this->_client = Auspost::factory(['auth_key' => $settings['settings']['apiKey']])->get('postage');
        }
    }

    public function getAPIFields()
    {
        return [
            'apiKey',
        ];
    }

    public function getSettingsTemplate()
    {
        return 'postie/providers/' . self::$handle . '.html';
    }

    public function getServiceList()
    {
        return [
            'AUS_PARCEL_COURIER'                => 'AusPost Domestic Courier Post',
            'AUS_PARCEL_COURIER_SATCHEL_MEDIUM' => 'AusPost Domestic Courier Post Assessed Medium Satchel',
            'AUS_PARCEL_EXPRESS'                => 'AusPost Domestic Express Post',
            'AUS_PARCEL_EXPRESS_SATCHEL_500G'   => 'AusPost Domestic Express Post Small Satchel',
            'AUS_PARCEL_REGULAR'                => 'AusPost Domestic Parcel Post',
            'AUS_PARCEL_REGULAR_SATCHEL_500G'   => 'AusPost Domestic Parcel Post Small Satchel',

            'INT_PARCEL_COR_OWN_PACKAGING' => 'AusPost International Courier',
            'INT_PARCEL_EXP_OWN_PACKAGING' => 'AusPost International Express',
            'INT_PARCEL_STD_OWN_PACKAGING' => 'AusPost International Standard',
            'INT_PARCEL_AIR_OWN_PACKAGING' => 'AusPost International Economy Air',
            'INT_PARCEL_SEA_OWN_PACKAGING' => 'AusPost International Economy Sea',
        ];
    }

    public function createShipping($handle, Commerce_OrderModel $order)
    {
        if (!$order->shippingAddress) {
            return false;
        }

        if (!$this->_client) {
            PostiePlugin::log('No auth_key was found', LogLevel::Error, true);

            return false;
        }

        // Check if this services is enabled
        $shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($handle);
        if (!$shippingMethod->isEnabled()) {
            return false;
        }

        $dimensions = $this->_getDimensions($order);
        $info = null;

        try {
            $prefix = explode('_', $handle)[0];

            if ($order->shippingAddress->country->iso == 'AU') {

                // Check if shipping method is domestic
                if ($prefix != 'AUS') {
                    return false;
                }

                // AusPost API postage/parcel/domestic/calculate call + logging
                $msg = 'AusPost API postage/parcel/domestic/calculate call for service ' . $handle;
                PostiePlugin::log($msg, LogLevel::Info);

                $info = $this->_client->calculateDomesticParcelPostage([
                    'from_postcode' => $this->_originAddress['postalCode'],
                    'to_postcode'   => $order->shippingAddress->zipCode,
                    'length'        => $dimensions['length'],
                    'width'         => $dimensions['width'],
                    'height'        => $dimensions['height'],
                    'weight'        => $dimensions['weight'],
                    'service_code'  => $handle,
                ]);
            } else {

                // Check if shipping method is international
                if ($prefix != 'INT') {
                    return false;
                }

                // Get match country code from Aus Pos country list
                $countryCode = $this->_getCountryCode($order->shippingAddress->country);

                if (!$countryCode) {
                    return false;
                }

                // AusPost API postage/parcel/international/calculate call + logging
                $msg = 'AusPost API postage/parcel/international/calculate call for service ' . $handle;
                PostiePlugin::log($msg, LogLevel::Info);

                $info = $this->_client->calculateInternationalParcelPostage([
                    'country_code' => $countryCode,
                    'weight'       => $dimensions['weight'],
                    'service_code' => $handle,
                ]);
            }
        } catch (BadResponseException $e) {

            $msg = str_replace(PHP_EOL, ' ', $e->getMessage());
            PostiePlugin::log($msg, LogLevel::Error, true);
        }

        if ($info && !isset($info['error'])) {
            return (float)$info['postage_result']['total_cost'];
        }

        return 0.00;
    }


    // Private Methods
    // =========================================================================

    private function _getCountryList()
    {
        if ($this->_countryList == null) {

            // AusPost API postage/country request
            $countryList = $this->_client->listCountries();
            $this->_countryList = $countryList;
        }

        return $this->_countryList;
    }

    private function _getCountryCode($country)
    {
        $countryList = $this->_getCountryList();

        foreach ($countryList['countries']['country'] as $countryListItem) {

            if (strtoupper($country) == $countryListItem['name']) {
                return $countryListItem['code'];
            }
        }

        return false;
    }

    private function _getDimensions(Commerce_OrderModel $order)
    {
        // Get Craft Commerce settings
        $settings = craft()->commerce_settings->getSettings();

        // Check for Craft Commerce weight settings
        switch ($settings->weightUnits) {

            // pounds
            case 'lb':
                $weight = $order->getTotalWeight() / 2.2;
                break;

            // g to kg
            case 'g':
                $weight = $order->getTotalWeight() / 1000;
                break;

            // kg
            default:
                $weight = $order->getTotalWeight();
        }

        // Get box package dimensions based on order line items
        $packageDimensions = parent::_getPackageDimensions($order);

        // Check for Craft Commerce dimension settings
        switch ($settings->dimensionUnits) {

            // Inches to cm
            case 'in':
                $length = $packageDimensions['length'] / 2.54;
                $width = $packageDimensions['width'] / 2.54;
                $height = $packageDimensions['height'] / 2.54;
                break;

            // Feet to cm
            case 'ft':
                $length = $packageDimensions['length'] / 30.48;
                $width = $packageDimensions['width'] / 30.48;
                $height = $packageDimensions['height'] / 30.48;
                break;

            // mm to cm
            case 'mm':
                $length = $packageDimensions['length'] / 10;
                $width = $packageDimensions['width'] / 10;
                $height = $packageDimensions['height'] / 10;
                break;

            // m to cm
            case 'm':
                $length = $packageDimensions['length'] * 100;
                $width = $packageDimensions['width'] * 100;
                $height = $packageDimensions['height'] * 100;
                break;

            // cm
            case 'cm':
            default:
                $length = $packageDimensions['length'];
                $width = $packageDimensions['width'];
                $height = $packageDimensions['height'];
        }

        return [
            'length' => (int)$length,
            'width'  => (int)$width,
            'height' => (int)$height,
            'weight' => (float)$weight,
        ];
    }
}