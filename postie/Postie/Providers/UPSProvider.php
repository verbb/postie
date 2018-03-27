<?php

namespace Postie\Providers;

use function Craft\craft;
use Craft\Commerce_OrderModel;
use Craft\DateTimeHelper;
use Craft\LogLevel;
use Craft\PostieHelper;
use Craft\PostiePlugin;
use Craft\StringHelper;

use Ups\Rate;
use Ups\Entity\Shipper;
use Ups\Entity\Shipment;
use Ups\Entity\Package;
use Ups\Entity\PackagingType;
use Ups\Entity\Dimensions;
use Ups\Entity\UnitOfMeasurement;

class UPSProvider extends BaseProvider
{
    // Properties
    // =========================================================================

    public static $handle = 'UPS';

    private $_client;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        parent::__construct();

        $this->_handle = self::$handle;

        $settings = $this->getProviderSettings(self::$handle);
        $this->_name = $settings['name'];

        // Get service list from config file to show in cp panel
        if (craft()->request->isCpRequest()) {
            $this->_services = $settings['services'];
        }

        $accessKey = '';
        $userId = '';
        $password = '';

        if (craft()->config->get('devMode')) {
            if (isset($settings['settings']['testApiKey'])) {
                $accessKey = $settings['settings']['testApiKey'];
            }
        } else {
            if (isset($settings['settings']['apiKey'])) {
                $accessKey = $settings['settings']['apiKey'];
            }
        }

        if (isset($settings['settings']['username'])) {
            $userId = $settings['settings']['username'];
        }

        if (isset($settings['settings']['password'])) {
            $password = $settings['settings']['password'];
        }

        $this->_client = new Rate($accessKey, $userId, $password);
    }

    public function getAPIFields()
    {
        return [
            'apiKey',
            'testApiKey',
            'username',
            'password',
        ];
    }

    public function getSettingsTemplate()
    {
        return 'postie/providers/' . self::$handle . '.html';
    }

    public function getServiceList()
    {
        return [
            // Domestic
            'S_AIR_1DAYEARLYAM' => 'UPS Next Day Air Early AM',
            'S_AIR_1DAY' => 'UPS Next Day Air',
            'S_AIR_1DAYSAVER' => 'Next Day Air Saver',
            'S_AIR_2DAYAM' => 'UPS Second Day Air AM',
            'S_AIR_2DAY' => 'UPS Second Day Air',
            'S_3DAYSELECT' => 'UPS Three-Day Select',
            'S_GROUND' => 'UPS Ground',
            'S_SURE_POST' => 'UPS Sure Post',

            // International
            'S_STANDARD' => 'UPS Standard',
            'S_WW_EXPRESS' => 'UPS Worldwide Express',
            'S_WW_EXPRESSPLUS' => 'UPS Worldwide Express Plus',
            'S_WW_EXPEDITED' => 'UPS Worldwide Expedited',
            'S_SAVER' => 'UPS Saver',
            'S_ACCESS_POINT' => 'UPS Access Point Economy',
        ];
    }

    // UPS makes a API call once it has all order details (as weight, address, etc). The response is a list of all
    // available services including the rate. We store that list in our $this->_service variable. Later, when the
    // createShipping() function is called, we just return the already stored rate for the relevant handle.
    public function getServices(Commerce_OrderModel $order = null)
    {
        if (!$order) {
            return $this->getServiceList();
        }

        if (!$order->shippingAddress || !$order->getLineItems()) {
            return false;
        }

        if (!$this->_client) {
            PostiePlugin::log('No authentication username was found', LogLevel::Error, true);

            return false;
        }

        $settings = $this->getProviderSettings(self::$handle);

        // Setup some caching mechanism to save API requests
        $signature = $this->_getSignature('UPS', $order);
        $cacheKey = 'postie-shipment-' . $signature;

        // Get services from the cache (if any)
        $this->_services = craft()->cache->get($cacheKey);

        if (craft()->config->get('disableCache', 'postie') !== true) {
            $this->_services = craft()->cache->get($cacheKey);

            // If is it not in the cache get services via API
            if ($this->_services !== false) {
                return $this->_services;
            }
        }

        $dimensions = $this->_getDimensions($order);
        $info = null;

        try {
            if ($order->shippingAddress->country->iso == 'US') {

                // Logging
                PostiePlugin::log('UPS API domestic rate service call', LogLevel::Info);

                // Specify our Account Number
                if (isset($settings['settings']['username'])) {
                    $userId = $settings['settings']['username'];
                } else {
                    return;
                }

                $shipper = new Shipper();
                $shipper->setShipperNumber($userId);

                $shipment = new Shipment();
                $shipment->setShipper($shipper);

                // From address
                $originAddress = $this->_originAddress;
                
                $destinationAddress = [
                    'name' => $order->shippingAddress->getFullName(),
                    'street1' => $order->shippingAddress->address1,
                    'street2' => $order->shippingAddress->address2,
                    'city' => $order->shippingAddress->city,
                    'state' => $order->shippingAddress->getState() ? $order->shippingAddress->getState()->abbreviation : $order->shippingAddress->getStateText(),
                    'zip' => $order->shippingAddress->zipCode,
                    'country' => $order->shippingAddress->getCountry()->iso,
                    'phone' => $order->shippingAddress->phone,
                    'company' => $order->shippingAddress->businessName,
                    'email' => $order->email,
                    'federal_tax_id' => $order->shippingAddress->businessTaxId
                ];

                $shipperAddress = $shipment->getShipper()->getAddress();

                $shipperAddress->setAddressLine1($originAddress['streetAddressLine1']);
                $shipperAddress->setAddressLine2($originAddress['streetAddressLine2']);
                $shipperAddress->setCity($originAddress['city']);
                $shipperAddress->setStateProvinceCode($originAddress['state']);
                $shipperAddress->setPostalCode($originAddress['postalCode']);

                // Destination Address
                $shipTo = $shipment->getShipTo();

                $shipToAddress = $shipTo->getAddress();
                $shipToAddress->setAddressLine1($destinationAddress['street1']);
                $shipToAddress->setAddressLine2($destinationAddress['street2']);
                $shipToAddress->setCity($destinationAddress['city']);
                $shipToAddress->setStateProvinceCode($destinationAddress['state']);
                $shipToAddress->setPostalCode($destinationAddress['zip']);
                $shipToAddress->setCountryCode($destinationAddress['country']);

                if ($dimensions['weight'] == 0) {
                    return false;
                }

                $package = new Package();
                $package->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
                $package->getPackageWeight()->setWeight($dimensions['weight']);

                $packageDimensions = new Dimensions();
                $packageDimensions->setHeight($dimensions['length']);
                $packageDimensions->setWidth($dimensions['width']);
                $packageDimensions->setLength($dimensions['height']);

                $unit = new UnitOfMeasurement;
                $unit->setCode(UnitOfMeasurement::UOM_IN);

                $packageDimensions->setUnitOfMeasurement($unit);
                $package->setDimensions($packageDimensions);

                $shipment->addPackage($package);

                $shipment->setNumOfPiecesInShipment(1);
            }

            // Perform the request
            $rates = $this->_client->shopRates($shipment);

            foreach ($rates->RatedShipment as $rate) {
                $serviceHandle = $this->_getServiceHandle($rate->Service->getCode());
                $serviceName = $rate->Service->getName();
                $monetaryValue = $rate->TotalCharges->MonetaryValue;

                $this->_services[$serviceHandle] = [
                    'name' => $serviceName,
                    'rate' => $monetaryValue,
                ];
            }

        } catch (\Exception $e) {
            $msg = str_replace(PHP_EOL, ' ', $e->getMessage());
            PostiePlugin::log($msg, LogLevel::Error, true);
        }

        // Set this in our cache for the next request to be much quicker
        craft()->cache->set($cacheKey, $this->_services, 0);

        return $this->_services;
    }

    public function createShipping($handle, Commerce_OrderModel $order)
    {
        if (isset($this->_services[$handle]['rate'])) {
            return $this->_services[$handle]['rate'];
        }

        return 0.00;
    }

    public function getServiceName($key)
    {
        return $this->_services[$key]['name'];
    }


    // Private Methods
    // =========================================================================

    private function _getDimensions(Commerce_OrderModel $order)
    {
        // Get Craft Commerce settings
        $settings = craft()->commerce_settings->getSettings();

        // Check for Craft Commerce weight settings
        switch ($settings->weightUnits) {

            // kg to lb
            case 'kg':
                $weight = $order->getTotalWeight() * 2.2;
                break;

            // g to lb
            case 'g':
                $weight = $order->getTotalWeight() * 0.0022;
                break;

            // pounds
            case 'lb':
            default:
                $weight = $order->getTotalWeight();
        }

        // Get box package dimensions based on order line items
        $packageDimensions = parent::_getPackageDimensions($order);

        // Check for Craft Commerce dimension settings
        switch ($settings->dimensionUnits) {

            // Feet to in
            case 'ft':
                $length = $packageDimensions['length'] * 12;
                $width = $packageDimensions['width'] * 12;
                $height = $packageDimensions['height'] * 12;
                break;

            // mm to in
            case 'mm':
                $length = $packageDimensions['length'] / 25.4;
                $width = $packageDimensions['width'] / 25.4;
                $height = $packageDimensions['height'] / 25.4;
                break;

            // m to in
            case 'm':
                $length = $packageDimensions['length'] / 0.0254;
                $width = $packageDimensions['width'] / 0.0254;
                $height = $packageDimensions['height'] / 0.0254;
                break;

            // cm to in
            case 'cm':
                $length = $packageDimensions['length'] / 2.54;
                $width = $packageDimensions['width'] / 2.54;
                $height = $packageDimensions['height'] / 2.54;
                break;

            // Inches
            case 'in':
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

    private function _parseServiceHandle($string)
    {
        $replace = [
            '&lt;sup&gt;&#8482;&lt;/sup&gt;',
            '&lt;sup&gt;&#174;&lt;/sup&gt;',
        ];
        $string = str_replace($replace, '', $string);
        $string = StringHelper::toSnakeCase($string);
        $string = strtoupper($string);

        return $string;
    }

    private function _getShippingMethodName($handle)
    {
        $shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($handle);

        if ($shippingMethod && $shippingMethod->isEnabled()) {
            return $shippingMethod->getName();
        }

        return false;
    }

    private static function _getServiceHandle($value)
    {
        $class = new \ReflectionClass('Ups\Entity\Service');
        $constants = array_flip($class->getConstants());

        return $constants[$value];
    }
}