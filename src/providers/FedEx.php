<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use FedEx\RateService\Request;
use FedEx\RateService\ComplexType;
use FedEx\RateService\ComplexType\RateRequest;
use FedEx\RateService\ComplexType\RequestedPackageLineItem;
use FedEx\RateService\SimpleType;
use FedEx\RateService\SimpleType\LinearUnits;
use FedEx\RateService\SimpleType\PaymentType;
use FedEx\RateService\SimpleType\RateRequestType;
use FedEx\RateService\SimpleType\WeightUnits;

class FedEx extends Provider
{
    // Properties
    // =========================================================================

    public $name = 'FedEx';


    // Public Methods
    // =========================================================================


    public function __construct()
    {
        parent::__construct();

        // Turn off SOAP wsdl caching
        ini_set("soap.wsdl_cache_enabled", "0");
    }

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('postie/providers/fedex', ['provider' => $this]);
    }

    public function getServiceList(): array
    {
        return [
            'FEDEX_1_DAY_FREIGHT'       => 'FedEx 1 Day Freight',
            'FEDEX_2_DAY'               => 'FedEx 2 Day',
            'FEDEX_2_DAY_AM'            => 'FedEx 2 Day AM',
            'FEDEX_2_DAY_FREIGHT'       => 'FedEx 2 DAY Freight',
            'FEDEX_3_DAY_FREIGHT'       => 'FedEx 3 Day Freight',
            'FEDEX_EXPRESS_SAVER'       => 'FedEx Express Saver',
            'FEDEX_FIRST_FREIGHT'       => 'FedEx First Freight',
            'FEDEX_FREIGHT_ECONOMY'     => 'FedEx Freight Economy',
            'FEDEX_FREIGHT_PRIORITY'    => 'FedEx Freight Priority',
            'FEDEX_GROUND'              => 'FedEx Ground',
            'FIRST_OVERNIGHT'           => 'FedEx First Overnight',
            'PRIORITY_OVERNIGHT'        => 'FedEx Priority Overnight',
            'STANDARD_OVERNIGHT'        => 'FedEx Standard Overnight',
            'GROUND_HOME_DELIVERY'      => 'FedEx Ground Home Delivery',
            'SAME_DAY'                  => 'FedEx Same Day',
            'SAME_DAY_CITY'             => 'FedEx Same Day City',
            'SMART_POST'                => 'FedEx Smart Post',

            // UK domestic services 
            'FEDEX_DISTANCE_DEFERRED'       => 'FedEx Distance Deferred',
            'FEDEX_NEXT_DAY_EARLY_MORNING'  => 'FedEx Next Day Early Morning',
            'FEDEX_NEXT_DAY_MID_MORNING'    => 'FedEx Next Day Mid Morning',
            'FEDEX_NEXT_DAY_AFTERNOON'      => 'FedEx Next Day Afternoon',
            'FEDEX_NEXT_DAY_END_OF_DAY'     => 'FedEx Next Day End of Day',
            'FEDEX_NEXT_DAY_FREIGHT'        => 'FedEx Next Day Freight',

            'INTERNATIONAL_ECONOMY'               => 'FedEx International Economy',
            'INTERNATIONAL_ECONOMY_FREIGHT'       => 'FedEx International Economy Freight',
            'INTERNATIONAL_FIRST'                 => 'FedEx International First',
            'INTERNATIONAL_PRIORITY'              => 'FedEx International Priority',
            'INTERNATIONAL_PRIORITY_FREIGHT'      => 'FedEx International Priority Freight',
            'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'FedEx Europe First International Priority',
        ];
    }

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $key = $this->settings['key'] ?? '';
        $password = $this->settings['password'] ?? '';
        $accountNumber = $this->settings['accountNumber'] ?? '';
        $meterNumber = $this->settings['meterNumber'] ?? '';
        $useTestEndpoint = $this->settings['useTestEndpoint'] ?? false;

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();
        $dimensions = $this->getDimensions($order, 'lb', 'in');

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $dimensions, $order);

        try {
            $rateRequest = new RateRequest();

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
            //
            //

            // Authentication & client details
            $rateRequest->WebAuthenticationDetail->UserCredential->Key = $key;
            $rateRequest->WebAuthenticationDetail->UserCredential->Password = $password;
            $rateRequest->ClientDetail->AccountNumber = $accountNumber;
            $rateRequest->ClientDetail->MeterNumber = $meterNumber;

            // Version
            $rateRequest->Version->ServiceId = 'crs';
            $rateRequest->Version->Major = 24;
            $rateRequest->Version->Minor = 0;
            $rateRequest->Version->Intermediate = 0;

            $rateRequest->ReturnTransitAndCommit = true;

            // Shipper
            $rateRequest->RequestedShipment->PreferredCurrency = $order->currency;
            $rateRequest->RequestedShipment->Shipper->Address->StreetLines = [$storeLocation->address1];
            $rateRequest->RequestedShipment->Shipper->Address->City = $storeLocation->city;
            $rateRequest->RequestedShipment->Shipper->Address->PostalCode = $storeLocation->zipCode;
            $rateRequest->RequestedShipment->Shipper->Address->CountryCode = $storeLocation->country->iso;

            // Recipient
            $rateRequest->RequestedShipment->Recipient->Address->StreetLines = [$order->shippingAddress->address1];
            $rateRequest->RequestedShipment->Recipient->Address->City = $order->shippingAddress->city;
            $rateRequest->RequestedShipment->Recipient->Address->PostalCode = $order->shippingAddress->zipCode;
            $rateRequest->RequestedShipment->Recipient->Address->CountryCode = $order->shippingAddress->country->iso;

            // Fedex can't handle 3-character states. Ignoring it is valid for international order
            if ($order->shippingAddress->country->iso == 'US' || $order->shippingAddress->country->iso == 'CA') {
                $rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = $storeLocation->state->abbreviation ?? '';
                $rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = $order->shippingAddress->state->abbreviation ?? '';
            }

            // Shipping charges payment
            $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = PaymentType::_SENDER;
            $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->AccountNumber = $accountNumber;
            $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->CountryCode = $storeLocation->country;

            // Rate request types
            $rateRequest->RequestedShipment->RateRequestTypes = [RateRequestType::_PREFERRED, RateRequestType::_LIST];

            // Create package line items
            $packageLineItems = $this->_createPackageLineItem($order);
            $rateRequest->RequestedShipment->PackageCount = count($packageLineItems);
            $rateRequest->RequestedShipment->RequestedPackageLineItems = $packageLineItems;

            $rateServiceRequest = new Request();

            // Check for devMode and set test or production endpoint
            if ($useTestEndpoint) {
                $rateServiceRequest->getSoapClient()->__setLocation(Request::TESTING_URL);
            } else {
                $rateServiceRequest->getSoapClient()->__setLocation(Request::PRODUCTION_URL);
            }

            $this->beforeSendPayload($this, $rateRequest, $order);

            // FedEx API rate service call
            $rateReply = $rateServiceRequest->getGetRatesReply($rateRequest);

            if (isset($rateReply->RateReplyDetails)) {
                foreach ($rateReply->RateReplyDetails as $rateReplyDetails) {
                    if (is_array($rateReplyDetails->RatedShipmentDetails)) {
                        // Find the lowest rate (for negotiated rates)
                        $ratedShipmentDetailRates = [];

                        foreach ($rateReplyDetails->RatedShipmentDetails as $key => $RatedShipmentDetail) {
                            $key = $RatedShipmentDetail->ShipmentRateDetail->RateType;

                            $ratedShipmentDetailRates[$key] = $RatedShipmentDetail->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount;
                        }

                        $rate = min($ratedShipmentDetailRates);
                    } else {
                        $rate = $rateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount;
                    }

                    if (!$rateReplyDetails->ServiceType) {
                        Provider::error($this, 'Service Type is not defined');
                        continue;
                    }

                    if (!$rate) {
                        Provider::error($this, 'No rate for ' . $rateReplyDetails->ServiceType);
                        continue;
                    }

                    $this->_rates[$rateReplyDetails->ServiceType] = [
                        'amount' => $rate,
                        'options' => [
                            'ServiceType' => $rateReplyDetails->ServiceType ?? '',
                            'ServiceDescription' => $rateReplyDetails->ServiceDescription->Description ?? '',
                            'packagingType' => $rateReplyDetails->PackagingType ?? '',
                            'deliveryStation' => $rateReplyDetails->DeliveryStation ?? '',
                            'deliveryDayOfWeek' => $rateReplyDetails->DeliveryDayOfWeek ?? '',
                            'deliveryTimestamp' => $rateReplyDetails->DeliveryTimestamp ?? '',
                            'transitTime' => $rateReplyDetails->TransitTime ?? '',
                            'destinationAirportId' => $rateReplyDetails->DestinationAirportId ?? '',
                            'ineligibleForMoneyBackGuarantee' => $rateReplyDetails->IneligibleForMoneyBackGuarantee ?? '',
                            'originServiceArea' => $rateReplyDetails->OriginServiceArea ?? '',
                            'destinationServiceArea' => $rateReplyDetails->DestinationServiceArea ?? '',
                        ],
                    ];
                }
            } elseif (isset($rateReply->Notifications)) {
                foreach ($rateReply->Notifications as $message) {
                    Provider::error($this, 'Rate Error: ' . $message->Message);
                }
            } else {
                Provider::error($this, 'Unable to fetch rates: `' . json_encode($rateReply) . '`.');
            }

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'response' => $rateReply,
                'order' => $order,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;

        } catch (\Throwable $e) {
            // Craft::dump($e->getMessage());
            Provider::error($this, 'API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _createPackageLineItem($order)
    {
        $packages = [];
        $dimensions = $this->getDimensions($order, 'lb', 'in');

        // Handle a maxiumum weight for packages
        $totalPackages = $this->getSplitBoxWeights($dimensions['weight'], 150);

        foreach ($totalPackages as $weight) {
            // Assuming we pack all order line items into one package to save shipping costs we creating just one package line item
            $packageLineItem = new RequestedPackageLineItem();

            // Weight
            $packageLineItem->Weight->Units = WeightUnits::_LB;
            $packageLineItem->Weight->Value = $weight;

            // Dimensions
            $packageLineItem->Dimensions->Units = LinearUnits::_IN;
            $packageLineItem->Dimensions->Length = $dimensions['length'];
            $packageLineItem->Dimensions->Width = $dimensions['width'];
            $packageLineItem->Dimensions->Height = $dimensions['height'];

            $packageLineItem->GroupPackageCount = 1;

            $packages[] = $packageLineItem;
        }

        return $packages;
    }
}
