<?php

namespace Postie\Providers;

use function Craft\craft;
use Craft\Commerce_OrderModel;
use Craft\LogLevel;
use Craft\PostieHelper;
use Craft\PostiePlugin;
use FedEx\RateService\Request;
use FedEx\RateService\ComplexType;
use FedEx\RateService\SimpleType;

class FedExProvider extends BaseProvider
{
    // Properties
    // =========================================================================

    public static $handle = 'fedEx';

    private $_settings;

    // Public Methods
    // =========================================================================

    public function __construct()
    {
        parent::__construct();

        $this->_handle = self::$handle;

        $this->_settings = $this->getProviderSettings(self::$handle);
        $this->_name = $this->_settings['name'];
        // $this->_services = $this->_settings['services'];

        //turn off SOAP wsdl caching
        ini_set("soap.wsdl_cache_enabled", "0");
    }

    public function getAPIFields()
    {
        return [
            'accountNumber',
            'meterNumber',
            'key',
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
            'FEDEX_1_DAY_FREIGHT'    => 'FedEx Domestic 1 Day Freight',
            'FEDEX_2_DAY'            => 'FedEx Domestic 2 Day',
            'FEDEX_2_DAY_AM'         => 'FedEx Domestic 2 Day AM',
            'FEDEX_2_DAY_FREIGHT'    => 'FedEx Domestic 2 DAY Freight',
            'FEDEX_3_DAY_FREIGHT'    => 'FedEx Domestic 3 Day Freight',
            'FEDEX_EXPRESS_SAVER'    => 'FedEx Domestic Express Saver',
            'FEDEX_FIRST_FREIGHT'    => 'FedEx Domestic First Freight',
            'FEDEX_FREIGHT_ECONOMY'  => 'FedEx Domestic Freight Economy',
            'FEDEX_FREIGHT_PRIORITY' => 'FedEx Domestic Freight Priority',
            'FEDEX_GROUND'           => 'FedEx Domestic Ground',
            'FIRST_OVERNIGHT'        => 'FedEx Domestic First Overnight',
            'PRIORITY_OVERNIGHT'     => 'FedEx Domestic Priority Overnight',
            'STANDARD_OVERNIGHT'     => 'FedEx Domestic Standard Overnight',
            'GROUND_HOME_DELIVERY'   => 'FedEx Domestic Ground Home Delivery',
            'SMART_POST'             => 'FedEx Domestic Smart Post',

            'INTERNATIONAL_ECONOMY'               => 'FedEx International Economy',
            'INTERNATIONAL_ECONOMY_FREIGHT'       => 'FedEx International Economy Freight',
            'INTERNATIONAL_FIRST'                 => 'FedEx International First',
            'INTERNATIONAL_PRIORITY'              => 'FedEx International Priority',
            'INTERNATIONAL_PRIORITY_FREIGHT'      => 'FedEx International Priority Freight',
            'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'FedEx Europe First International Priority',
        ];
    }

    // We make just one API call for all FedEx services and store that list inclusive the rate in our $this->_service
    // variable. Later, when the createShipping() function is called, we just return the already stored rate for the
    // relevant handle.
    public function getServices(Commerce_OrderModel $order = null)
    {
        if (!$order) {
            return $this->getServiceList();
        }

        if (!$order->shippingAddress || !$order->getLineItems()) {
            return false;
        }

        // Setup some caching mechanism to save API requests
        $signature = $this->_getSignature('FedEx', $order);
        $cacheKey = 'postie-shipment-' . $signature;

        // Get services from the cache (if any)
        $this->_services = craft()->cache->get($cacheKey);

        // If is it not in the cache get services via API
        if ($this->_services !== false) {
            return $this->_services;
        }

        $response = null;

        try {
            $rateRequest = new ComplexType\RateRequest();

            // Authentication & client details
            if (isset($this->_settings['settings']['key'])) {
                $rateRequest->WebAuthenticationDetail->UserCredential->Key = $this->_settings['settings']['key'];
            }

            if (isset($this->_settings['settings']['password'])) {
                $rateRequest->WebAuthenticationDetail->UserCredential->Password = $this->_settings['settings']['password'];
            }

            if (isset($this->_settings['settings']['accountNumber'])) {
                $rateRequest->ClientDetail->AccountNumber = $this->_settings['settings']['accountNumber'];
            }

            if (isset($this->_settings['settings']['meterNumber'])) {
                $rateRequest->ClientDetail->MeterNumber = $this->_settings['settings']['meterNumber'];
            }

            // Version
            $rateRequest->Version->ServiceId = 'crs';
            $rateRequest->Version->Major = 10;
            $rateRequest->Version->Minor = 0;
            $rateRequest->Version->Intermediate = 0;

            if ($order->shippingAddress->country->iso == 'US') {

                // Logging
                PostiePlugin::log('FedEx API domestic rate service call', LogLevel::Info);

                // If shipping to US you have to provide the state
                $rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = $this->_originAddress['state'];
                $rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = $order->shippingAddress->state->abbreviation;
            } else {

                // Logging
                PostiePlugin::log('FedEx API international rate service call', LogLevel::Info);
            }

            // Shipper
            $rateRequest->RequestedShipment->Shipper->Address->StreetLines = [$this->_originAddress['streetAddressLine1']];
            $rateRequest->RequestedShipment->Shipper->Address->City = $this->_originAddress['city'];

            $rateRequest->RequestedShipment->Shipper->Address->PostalCode = $this->_originAddress['postalCode'];
            $rateRequest->RequestedShipment->Shipper->Address->CountryCode = $this->_originAddress['country'];

            // Recipient
            $rateRequest->RequestedShipment->Recipient->Address->StreetLines = [$order->shippingAddress->address1];
            $rateRequest->RequestedShipment->Recipient->Address->City = $order->shippingAddress->city;

            $rateRequest->RequestedShipment->Recipient->Address->PostalCode = $order->shippingAddress->zipCode;
            $rateRequest->RequestedShipment->Recipient->Address->CountryCode = $order->shippingAddress->country->iso;

            // Shipping charges payment
            $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = SimpleType\PaymentType::_SENDER;

            if (isset($this->_settings['settings']['accountNumber'])) {
                $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->AccountNumber = $this->_settings['settings']['accountNumber'];
            }
            $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->CountryCode = $this->_originAddress['country'];

            // Rate request types
            $rateRequest->RequestedShipment->RateRequestTypes = [SimpleType\RateRequestType::_ACCOUNT];

            // Create package line item
            $packageLineItems = $this->_createPackageLineItem($order);
            $rateRequest->RequestedShipment->PackageCount = count($packageLineItems);
            $rateRequest->RequestedShipment->RequestedPackageLineItems = $packageLineItems;

            $rateServiceRequest = new Request();

            // Check for devMode and set test or production endpoint
            if (craft()->config->get('devMode')) {
                $rateServiceRequest->getSoapClient()->__setLocation(Request::TESTING_URL);
            } else {
                $rateServiceRequest->getSoapClient()->__setLocation(Request::PRODUCTION_URL);
            }

            // FedEx API rate service call
            $response = $rateServiceRequest->getGetRatesReply($rateRequest);
        } catch (\SoapFault $e) {

            $msg = str_replace(PHP_EOL, ' ', $e->getMessage());
            $this->_logError($msg, $cacheKey);
        }

        if(!$response) {
            $this->_logError('Got empty response', $cacheKey);
        }
        
        if (isset($response->HighestSeverity) && ($response->HighestSeverity == 'ERROR' || $response->HighestSeverity == 'FAILURE')) {

            if (is_array($response->Notifications)) {

                foreach ($response->Notifications as $message) {
                    PostiePlugin::log($message->Message, LogLevel::Error, true);
                }
            } else {
                PostiePlugin::log($response->Notifications->Message, LogLevel::Error, true);
            }
        } else {
            if (!isset($response->RateReplyDetails) || !is_array($response->RateReplyDetails)) {
                $this->_logError('Got empty response RateReplyDetails', $cacheKey);
            }

            if (isset($response->RateReplyDetails)) {
                foreach ($response->RateReplyDetails as $rateReplyDetails) {

                    if (is_array($rateReplyDetails->RatedShipmentDetails)) {
                        $rate = $rateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount;
                    } else {
                        $rate = $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount;
                    }

                    if (!$rateReplyDetails->ServiceType) {
                        $this->_logError('Got no ServiceType', $cacheKey);
                    }

                    if (!$rate) {
                        $this->_logError('Got no rate for ' . $rateReplyDetails->ServiceType, $cacheKey);
                    }

                    $shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($rateReplyDetails->ServiceType);

                    if ($shippingMethod && $shippingMethod->isEnabled()) {
                        $this->_services[$rateReplyDetails->ServiceType] = [
                            'name' => $shippingMethod->getName(),
                            'rate' => $rate,
                        ];
                    }
                }
            }
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

    private function _createPackageLineItem(Commerce_OrderModel $order)
    {
        // Get Craft Commerce settings
        $settings = craft()->commerce_settings->getSettings();

        // Assuming we pack all order line items into one package to save
        // shipping costs we creating just one package line item
        $packageLineItem = new ComplexType\RequestedPackageLineItem();

        // Weight
        $packageLineItem->Weight->Value = $order->getTotalWeight();

        // Check for Craft Commerce weight settings
        switch ($settings->weightUnits) {

            // pounds
            case 'lb':
                $packageLineItem->Weight->Units = SimpleType\WeightUnits::_LB;
                break;

            // g to kg
            case 'g':
                $packageLineItem->Weight->Units = SimpleType\WeightUnits::_KG;
                $packageLineItem->Weight->Value = $order->getTotalWeight() / 1000;
                break;

            // kg
            default:
                $packageLineItem->Weight->Units = SimpleType\WeightUnits::_KG;
        }

        // Get box package dimensions based on order line items
        $packageDimensions = parent::_getPackageDimensions($order);

        // Dimensions
        $packageLineItem->Dimensions->Length = $packageDimensions['length'];
        $packageLineItem->Dimensions->Width = $packageDimensions['width'];
        $packageLineItem->Dimensions->Height = $packageDimensions['height'];

        // Check for Craft Commerce dimension settings
        switch ($settings->dimensionUnits) {

            // Inches
            case 'in':
                $packageLineItem->Dimensions->Units = SimpleType\LinearUnits::_IN;
                break;

            // Feet to inches
            case 'ft':
                $packageLineItem->Dimensions->Units = SimpleType\LinearUnits::_IN;
                $packageLineItem->Dimensions->Length = $packageDimensions['length'] * 12;
                $packageLineItem->Dimensions->Width = $packageDimensions['width'] * 12;
                $packageLineItem->Dimensions->Height = $packageDimensions['height'] * 12;
                break;

            // mm to cm
            case 'mm':
                $packageLineItem->Dimensions->Units = SimpleType\LinearUnits::_CM;
                $packageLineItem->Dimensions->Length = $packageDimensions['length'] / 10;
                $packageLineItem->Dimensions->Width = $packageDimensions['width'] / 10;
                $packageLineItem->Dimensions->Height = $packageDimensions['height'] / 10;
                break;

            // m to cm
            case 'm':
                $packageLineItem->Dimensions->Units = SimpleType\LinearUnits::_CM;
                $packageLineItem->Dimensions->Length = $packageDimensions['length'] * 100;
                $packageLineItem->Dimensions->Width = $packageDimensions['width'] * 100;
                $packageLineItem->Dimensions->Height = $packageDimensions['height'] * 100;
                break;

            // cm
            case 'cm':
            default:
                $packageLineItem->Dimensions->Units = SimpleType\LinearUnits::_CM;
        }

        $packageLineItem->GroupPackageCount = 1;

        return [$packageLineItem];
    }

    private function _logError($msg, $cacheKey)
    {
        // Error logging
        PostiePlugin::log($msg, LogLevel::Error, true);

        // Set empty array for caching purposes
        $this->_services = [];

        // Set this in our cache for the next request to be much quicker
        craft()->cache->set($cacheKey, $this->_services, 0);

        return $this->_services;
    }
}