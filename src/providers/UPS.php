<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Ups\Rate;
use Ups\Entity\Shipper;
use Ups\Entity\Address;
use Ups\Entity\ShipFrom;
use Ups\Entity\Shipment;
use Ups\Entity\Package;
use Ups\Entity\PackagingType;
use Ups\Entity\Dimensions;
use Ups\Entity\UnitOfMeasurement;
use Ups\Entity\RateInformation;
use Ups\Entity\PaymentInformation;

class UPS extends Provider
{
    // Properties
    // =========================================================================

    public $name = 'UPS';


    // Public Methods
    // =========================================================================

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('postie/providers/ups', ['provider' => $this]);
    }

    public function getServiceList(): array
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

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $client = $this->_getClient();

        if (!$client) {
            Provider::error($this, 'Unable to communicate with API.');
            return false;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();
        $dimensions = $this->getDimensions($order, 'lb', 'in');

        //
        // TESTING
        //
        // $country = Commerce::getInstance()->countries->getCountryByIso('US');
        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'CA');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->address1 = 'One Infinite Loop';
        // $storeLocation->city = 'Cupertino';
        // $storeLocation->zipCode = '95014';
        // $storeLocation->stateId = $state->id;
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->address1 = '1600 Amphitheatre Parkway';
        // $order->shippingAddress->city = 'Mountain View';
        // $order->shippingAddress->zipCode = '94043';
        // $order->shippingAddress->stateId = $state->id;
        // $order->shippingAddress->countryId = $country->id;
        //
        // TESTING
        //

        try {
            $shipment = new Shipment();

            $shipFromAddress = new Address();
            $shipFromAddress->setPostalCode($storeLocation->zipCode);

            // UPS can't handle 3-character states. Ignoring it is valid for international order
            if ($order->shippingAddress->country->iso == 'US' || $order->shippingAddress->country->iso == 'CA') {
                $state = $storeLocation->state->abbreviation ?? '';
                
                $shipFromAddress->setStateProvinceCode($state);
            }

            $shipFrom = new ShipFrom();
            $shipFrom->setAddress($shipFromAddress);

            $shipment->setShipFrom($shipFrom);

            $shipTo = $shipment->getShipTo();
            $shipToAddress = $shipTo->getAddress();
            $shipToAddress->setPostalCode($order->shippingAddress->zipCode);

            $package = new Package();
            $package->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
            $package->getPackageWeight()->setWeight($dimensions['weight']);
            
            $weightUnit = new UnitOfMeasurement;
            $weightUnit->setCode(UnitOfMeasurement::UOM_LBS);
            $package->getPackageWeight()->setUnitOfMeasurement($weightUnit);

            $packageDimensions = new Dimensions();
            $packageDimensions->setHeight($dimensions['height']);
            $packageDimensions->setWidth($dimensions['width']);
            $packageDimensions->setLength($dimensions['length']);

            $unit = new UnitOfMeasurement;
            $unit->setCode(UnitOfMeasurement::UOM_IN);

            $packageDimensions->setUnitOfMeasurement($unit);
            $package->setDimensions($packageDimensions);

            $shipment->addPackage($package);

            $negotiatedRates = $this->settings['negotiatedRates'] ?? '';
            $accountNumber = $this->settings['accountNumber'] ?? '';

            // Check for negotiated rates
            if ($negotiatedRates && $accountNumber) {
                $rateInformation = new RateInformation;
                $rateInformation->setNegotiatedRatesIndicator(1);
                $shipment->setRateInformation($rateInformation);

                $shipper = $shipment->getShipper();
                $shipper->setShipperNumber($accountNumber);
                $shipment->setPaymentInformation(new PaymentInformation('prepaid', (object)['AccountNumber' => $accountNumber]));
            }
        
            // Perform the request
            $rates = $this->_client->shopRates($shipment);

            if (isset($rates->RatedShipment)) {
                foreach ($rates->RatedShipment as $rate) {
                    $serviceHandle = $this->_getServiceHandle($rate->Service->getCode());

                    $amount = $rate->TotalCharges->MonetaryValue ?? '';

                    // If we're using negotiated rates, return that, not the normal values
                    $negotiatedRates = $rate->NegotiatedRates ?? '';

                    if ($negotiatedRates) {
                        $amount = $rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue ?? '';
                    }

                    $this->_rates[$serviceHandle] = $amount;
                }
            }
        } catch (\Throwable $e) {
            // Craft::dump($e->getMessage());
            Provider::error($this, 'API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                $accessKey = $this->settings['testApiKey'];
            } else {
                $accessKey = $this->settings['apiKey'];
            }

            $userId = $this->settings['username'];
            $password = $this->settings['password'];
            
            $this->_client = new Rate($accessKey, $userId, $password);
        }

        return $this->_client;
    }

    private static function _getServiceHandle($value)
    {
        $class = new \ReflectionClass('Ups\Entity\Service');
        $constants = array_flip($class->getConstants());

        return $constants[$value];
    }
}
