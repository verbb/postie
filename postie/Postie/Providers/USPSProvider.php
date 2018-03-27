<?php

namespace Postie\Providers;

use function Craft\craft;
use Craft\Commerce_OrderModel;
use Craft\DateTimeHelper;
use Craft\LogLevel;
use Craft\PostieHelper;
use Craft\PostiePlugin;
use Craft\StringHelper;
use USPS\Rate;
use USPS\RatePackage;

class USPSProvider extends BaseProvider
{
    // Properties
    // =========================================================================

    public static $handle = 'USPS';

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

        // Initiate client and set the username provided from usps
        if (isset($settings['settings']['username'])) {
            $this->_client = new Rate($settings['settings']['username']);
        }
    }

    public function getAPIFields()
    {
        return [
            'username',
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
            'PRIORITY_MAIL_EXPRESS_1_DAY'                                                   => 'USPS Priority Mail Express 1-Day',
            'PRIORITY_MAIL_EXPRESS_1_DAY_HOLD_FOR_PICKUP'                                   => 'USPS Priority Mail Express 1-Day Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_SUNDAY_HOLIDAY_DELIVERY'                           => 'USPS Priority Mail Express 1-Day Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE'                                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'        => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'                          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'  => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE'                         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Sunday/Holiday Delivery',

            'PRIORITY_MAIL_EXPRESS_2_DAY'                                           => 'USPS Priority Mail Express 2-Day',
            'PRIORITY_MAIL_EXPRESS_2_DAY_HOLD_FOR_PICKUP'                           => 'USPS Priority Mail Express 2-Day Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE'                        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE'                  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE'                 => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope Hold For Pickup',

            'PRIORITY_MAIL_1_DAY'                              => 'USPS Priority Mail 1-Day',
            'PRIORITY_MAIL_1_DAY_LARGE_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Large Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_MEDIUM_FLAT_RATE_BOX'         => 'USPS Priority Mail 1-Day Medium Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Small Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_FLAT_RATE_ENVELOPE'           => 'USPS Priority Mail 1-Day Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_PADDED_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail 1-Day Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Small Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_WINDOW_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Window Flat Rate Envelope',

            'FIRST_CLASS_MAIL'                   => 'USPS First-Class Mail',
            'FIRST_CLASS_MAIL_STAMPED_LETTER'    => 'USPS First-Class Mail Stamped Letter',
            'FIRST_CLASS_MAIL_METERED_LETTER'    => 'USPS First-Class Mail Metered Letter',
            'FIRST_CLASS_MAIL_LARGE_ENVELOPE'    => 'USPS First-Class Mail Large Envelope',
            'FIRST_CLASS_MAIL_POSTCARDS'         => 'USPS First-Class Mail Postcards',
            'FIRST_CLASS_MAIL_LARGE_POSTCARDS'   => 'USPS First-Class Mail Large Postcards',
            'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => 'USPS First-Class Package Service - Retail',

            'MEDIA_MAIL_PARCEL'   => 'USPS Media Mail Parcel',
            'LIBRARY_MAIL_PARCEL' => 'USPS Library Mail Parcel',


            // International
            'USPS_GXG_ENVELOPES'                  => 'USPS Global Express Guaranteed Envelopes',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'USPS Priority Mail Express International',

            'PRIORITY_MAIL_INTERNATIONAL'                      => 'USPS Priority Mail International',
            'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX'  => 'USPS Priority Mail International Large Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail International Medium Flat Rate Box',

            'FIRST_CLASS_MAIL_INTERNATIONAL'            => 'USPS First-Class Mail International',
            'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => 'USPS First-Class Package International Service',
        ];
    }

    // USPS makes a API call once it has all order details (as weight, address, etc). The response is a list of all
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

        // Setup some caching mechanism to save API requests
        $signature = $this->_getSignature('USPS', $order);
        $cacheKey = 'postie-shipment-' . $signature;

        // Get services from the cache (if any)
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
                PostiePlugin::log('USPS API domestic rate service call', LogLevel::Info);

                // Create new package object and assign the properties
                // apparently the order you assign them is important so make sure
                // to set them as the example below
                // set the RatePackage for more info about the constants
                $package = new RatePackage();

                // Set service
                $package->setService(RatePackage::SERVICE_ALL);
                $package->setFirstClassMailType(RatePackage::MAIL_TYPE_PARCEL);

                $package->setZipOrigination($this->_originAddress['postalCode']);
                $package->setZipDestination($order->shippingAddress->zipCode);
                // weights are in pounds and ounces, no metric system (kg)
                $package->setPounds($dimensions['weight']);
                $package->setOunces(0);
                $package->setContainer('');
                $package->setSize(RatePackage::SIZE_REGULAR);
                $package->setField('Machinable', true);

                // add the package to the client stack
                $this->_client->addPackage($package);
            } else {

                // Logging
                PostiePlugin::log('USPS API international rate service call', LogLevel::Info);

                // Set international flag
                $this->_client->setInternationalCall(true);
                $this->_client->addExtraOption('Revision', 2);

                $package = new RatePackage();
                // weights are in pounds and ounces, no metric system (kg)
                $package->setPounds($dimensions['weight']);
                $package->setOunces(0);
                $package->setField('Machinable', 'True');
                $package->setField('MailType', 'Package');
                // value of content necessary for export
                $package->setField('ValueOfContents', $order->getTotalSaleAmount());
                $package->setField('Country', $order->shippingAddress->country);

                // Check if dimensions greater then 12 inches then set LARGE package
                if ($dimensions['width'] > 12 || $dimensions['height'] > 12 || $dimensions['length'] > 12) {
                    $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);
                    $package->setField('Size', 'LARGE');
                } else {
                    $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);
                    $package->setField('Size', 'REGULAR');
                }

                $package->setField('Width', $dimensions['width']);
                $package->setField('Length', $dimensions['height']);
                $package->setField('Height', $dimensions['length']);
                // Girth are relevant when CONTAINER_NONRECTANGULAR
                $package->setField('Girth', $dimensions['width'] * 2 + $dimensions['length'] * 2);

                $package->setField('OriginZip', $this->_originAddress['postalCode']);
                $package->setField('CommercialFlag', 'N');
                $package->setField('AcceptanceDateTime', DateTimeHelper::toIso8601(time()));
                $package->setField('DestinationPostalCode', $order->shippingAddress->zipCode);

                // add the package to the client stack
                $this->_client->addPackage($package);
            }
        } catch (\Exception $e) {

            $msg = str_replace(PHP_EOL, ' ', $e->getMessage());
            PostiePlugin::log($msg, LogLevel::Error, true);
        }

        // Perform the request
        $this->_client->getRate();

        // handle response
        if ($this->_client->isSuccess()) {
            $arrayResponse = $this->_client->getArrayResponse();

            if (isset($arrayResponse['RateV4Response']['Package']['Postage'])) {

                foreach ($arrayResponse['RateV4Response']['Package']['Postage'] as $service) {

                    $serviceHandle = $this->_parseServiceHandle($service['MailService']);
                    $serviceName = $this->_getShippingMethodName($serviceHandle);

                    if ($serviceName) {
                        $this->_services[$serviceHandle] = [
                            'name' => $serviceName,
                            'rate' => $service['Rate'],
                        ];
                    }
                }
            } else {

                if (isset($arrayResponse['IntlRateV2Response']['Package']['Service'])) {

                    foreach ($arrayResponse['IntlRateV2Response']['Package']['Service'] as $service) {

                        $serviceHandle = $this->_parseServiceHandle($service['SvcDescription']);
                        $serviceName = $this->_getShippingMethodName($serviceHandle);

                        if ($serviceName) {
                            $this->_services[$serviceHandle] = [
                                'name' => $serviceName,
                                'rate' => $service['Postage'],
                            ];
                        }
                    }
                } else {
                    // Error logging
                    PostiePlugin::log('No Services found', LogLevel::Error, true);

                    // Set empty array for caching purposes
                    $this->_services = [];
                }
            }
        } else {
            // Error logging
            PostiePlugin::log($this->_client->getErrorMessage(), LogLevel::Error, true);

            // Set empty array for caching purposes
            $this->_services = [];
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
}