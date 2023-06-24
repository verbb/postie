<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use FedEx\RateService\Request;
use FedEx\RateService\ComplexType;
use FedEx\RateService\ComplexType\ContactAndAddress;
use FedEx\RateService\ComplexType\RateRequest;
use FedEx\RateService\ComplexType\RequestedPackageLineItem;
use FedEx\RateService\SimpleType;
use FedEx\RateService\SimpleType\LinearUnits;
use FedEx\RateService\SimpleType\PaymentType;
use FedEx\RateService\SimpleType\RateRequestType;
use FedEx\RateService\SimpleType\ServiceOptionType;
use FedEx\RateService\SimpleType\WeightUnits;

class FedEx extends Provider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'lb';
    public $dimensionUnit = 'in';

    private $maxWeight = 68038.9; // 150lbs


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        parent::__construct();

        // Turn off SOAP wsdl caching
        ini_set("soap.wsdl_cache_enabled", "0");
    }

    public static function displayName(): string
    {
        return Craft::t('postie', 'FedEx');
    }

    public function getServiceList(): array
    {
        return [
            // Domestic
            'FEDEX_1_DAY_FREIGHT' => 'FedEx 1 Day Freight',
            'FEDEX_2_DAY' => 'FedEx 2 Day',
            'FEDEX_2_DAY_AM' => 'FedEx 2 Day AM',
            'FEDEX_2_DAY_FREIGHT' => 'FedEx 2 DAY Freight',
            'FEDEX_3_DAY_FREIGHT' => 'FedEx 3 Day Freight',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
            'FEDEX_FIRST_FREIGHT' => 'FedEx First Freight',
            'FEDEX_FREIGHT_ECONOMY' => 'FedEx Freight Economy',
            'FEDEX_FREIGHT_PRIORITY' => 'FedEx Freight Priority',
            'FEDEX_GROUND' => 'FedEx Ground',
            'FIRST_OVERNIGHT' => 'FedEx First Overnight',
            'PRIORITY_OVERNIGHT' => 'FedEx Priority Overnight',
            'STANDARD_OVERNIGHT' => 'FedEx Standard Overnight',
            'GROUND_HOME_DELIVERY' => 'FedEx Ground Home Delivery',
            'SAME_DAY' => 'FedEx Same Day',
            'SAME_DAY_CITY' => 'FedEx Same Day City',
            'SMART_POST' => 'FedEx Smart Post',

            // UK domestic services 
            'FEDEX_DISTANCE_DEFERRED' => 'FedEx Distance Deferred',
            'FEDEX_NEXT_DAY_EARLY_MORNING' => 'FedEx Next Day Early Morning',
            'FEDEX_NEXT_DAY_MID_MORNING' => 'FedEx Next Day Mid Morning',
            'FEDEX_NEXT_DAY_AFTERNOON' => 'FedEx Next Day Afternoon',
            'FEDEX_NEXT_DAY_END_OF_DAY' => 'FedEx Next Day End of Day',
            'FEDEX_NEXT_DAY_FREIGHT' => 'FedEx Next Day Freight',

            // International
            'INTERNATIONAL_ECONOMY' => 'FedEx International Economy',
            'INTERNATIONAL_ECONOMY_FREIGHT' => 'FedEx International Economy Freight',
            'INTERNATIONAL_ECONOMY_DISTRIBUTION' => 'FedEx International Economy Distribution',
            'INTERNATIONAL_FIRST' => 'FedEx International First',
            'INTERNATIONAL_PRIORITY' => 'FedEx International Priority',
            'INTERNATIONAL_PRIORITY_FREIGHT' => 'FedEx International Priority Freight',
            'INTERNATIONAL_PRIORITY_DISTRIBUTION' => 'FedEx International Priority Distribution',
            'INTERNATIONAL_PRIORITY_EXPRESS' => 'FedEx International Priority Express',
            'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'FedEx Europe First International Priority',
            'INTERNATIONAL_DISTRIBUTION_FREIGHT' => 'FedEx International Distribution',
        ];
    }

    public static function defineDefaultBoxes()
    {
        return [
            [
                'id' => 'fedex-1',
                'name' => 'FedEx® Small Box',
                'boxLength' => 12.375,
                'boxWidth' => 10.875,
                'boxHeight' => 1.5,
                'boxWeight' => 0.28125,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-2',
                'name' => 'FedEx® Small Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 2.625,
                'boxWeight' => 0.28125,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-3',
                'name' => 'FedEx® Medium Box',
                'boxLength' => 13.25,
                'boxWidth' => 11.5,
                'boxHeight' => 2.375,
                'boxWeight' => 0.40625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-4',
                'name' => 'FedEx® Medium Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 4.375,
                'boxWeight' => 0.40625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-5',
                'name' => 'FedEx® Large Box',
                'boxLength' => 17.5,
                'boxWidth' => 12.365,
                'boxHeight' => 3,
                'boxWeight' => 0.90625,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-6',
                'name' => 'FedEx® Large Box',
                'boxLength' => 11.25,
                'boxWidth' => 8.75,
                'boxHeight' => 7.75,
                'boxWeight' => 0.5875,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-7',
                'name' => 'FedEx® Extra Large Box',
                'boxLength' => 11.875,
                'boxWidth' => 11,
                'boxHeight' => 10.75,
                'boxWeight' => 1.25,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-8',
                'name' => 'FedEx® Extra Large Box',
                'boxLength' => 15.75,
                'boxWidth' => 14.125,
                'boxHeight' => 6,
                'boxWeight' => 1.875,
                'maxWeight' => 20,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-9',
                'name' => 'FedEx® Pak',
                'boxLength' => 15.5,
                'boxWidth' => 12,
                'boxHeight' => 1.5,
                'boxWeight' => 0.0625,
                'maxWeight' => 5.5,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-10',
                'name' => 'FedEx® Envelope',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.25,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-11',
                'name' => 'FedEx® 10kg Box',
                'boxLength' => 15.81,
                'boxWidth' => 12.94,
                'boxHeight' => 10.19,
                'boxWeight' => 1.9375,
                'maxWeight' => 22,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-12',
                'name' => 'FedEx® 25kg Box',
                'boxLength' => 21.56,
                'boxWidth' => 16.56,
                'boxHeight' => 13.19,
                'boxWeight' => 3.5625,
                'maxWeight' => 55,
                'enabled' => true,
            ],
            [
                'id' => 'fedex-13',
                'name' => 'FedEx® Tube',
                'boxLength' => 38,
                'boxWidth' => 6,
                'boxHeight' => 6,
                'boxWeight' => 1,
                'maxWeight' => 20,
                'enabled' => true,
            ],
        ];
    }

    public function getWeightUnitOptions()
    {
        return [
            ['label' => Craft::t('commerce', 'Kilograms (kg)'), 'value' => 'kg'],
            ['label' => Craft::t('commerce', 'Pounds (lb)'), 'value' => 'lb'],
        ];
    }

    public function getDimensionUnitOptions()
    {
        return [
            ['label' => Craft::t('commerce', 'Centimeters (cm)'), 'value' => 'cm'],
            ['label' => Craft::t('commerce', 'Inches (in)'), 'value' => 'in'],
        ];
    }

    public function getMaxPackageWeight($order)
    {
        return $this->maxWeight;
    }

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        //
        // TESTING
        //
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('US', ['city' => 'Cupertino']);
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['city' => 'Mountain View']);

        // Canada 
        // $storeLocation = TestingHelper::getTestAddress('CA', ['city' => 'Toronto']);
        // $order->shippingAddress = TestingHelper::getTestAddress('CA', ['city' => 'Montreal']);

        // Freight
        // $order->currency = 'USD';
        //
        //
        //

        try {
            // Fetch rates for 'regular' rates (non-freight)
            $this->fetchShippingRate($order, $storeLocation, $packedBoxes);

            // If enabled, fetch again for freight rates. Only those are returned, so a separate call is needed
            if ($this->getSetting('enableFreight')) {
                $this->fetchShippingRate($order, $storeLocation, $packedBoxes, 'freight');
            }

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'order' => $order,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;
        } catch (\Throwable $e) {
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->_rates;
    }

    public function fetchShippingRate($order, $storeLocation, $packedBoxes, $requestType = '')
    {
        $rateRequest = new RateRequest();

        $rateRequest->WebAuthenticationDetail->UserCredential->Key = $this->getSetting('key');
        $rateRequest->WebAuthenticationDetail->UserCredential->Password = $this->getSetting('password');
        $rateRequest->ClientDetail->AccountNumber = $this->getSetting('accountNumber');
        $rateRequest->ClientDetail->MeterNumber = $this->getSetting('meterNumber');
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

        // Recipient
        $rateRequest->RequestedShipment->Recipient->Address->StreetLines = [$order->shippingAddress->address1];
        $rateRequest->RequestedShipment->Recipient->Address->City = $order->shippingAddress->city;
        $rateRequest->RequestedShipment->Recipient->Address->PostalCode = $order->shippingAddress->zipCode;

        if ($this->getSetting('residentialAddress')) {
            $rateRequest->RequestedShipment->Recipient->Address->Residential = true;
        }

        // Fedex can't handle 3-character states. Ignoring it is valid for international order
        if ($storeLocation->country) {
            $countryIso = $storeLocation->country->iso ?? '';
            $rateRequest->RequestedShipment->Shipper->Address->CountryCode = $countryIso;

            if ($countryIso == 'US' || $countryIso == 'CA') {
                $rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = $storeLocation->state->abbreviation ?? '';
            }
        }

        // Fedex can't handle 3-character states. Ignoring it is valid for international order
        if ($order->shippingAddress->country) {
            $countryIso = $order->shippingAddress->country->iso ?? '';
            $rateRequest->RequestedShipment->Recipient->Address->CountryCode = $countryIso;

            if ($countryIso == 'US' || $countryIso == 'CA') {
                $rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = $order->shippingAddress->state->abbreviation ?? '';
            }
        }

        // Shipping charges payment
        $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = PaymentType::_SENDER;
        $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->AccountNumber = $this->getSetting('accountNumber');
        $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->CountryCode = $storeLocation->country;

        // Rate request types
        $rateRequest->RequestedShipment->RateRequestTypes = [RateRequestType::_PREFERRED, RateRequestType::_LIST];

        if ($this->getSetting('enableOneRate')) {
            $rateRequest->RequestedShipment->VariableOptions = [ServiceOptionType::_FEDEX_ONE_RATE];
        }

        // Create package line items
        $packageLineItems = $this->_createPackageLineItem($order, $packedBoxes);
        $rateRequest->RequestedShipment->PackageCount = count($packageLineItems);
        $rateRequest->RequestedShipment->RequestedPackageLineItems = $packageLineItems;

        if ($requestType === 'freight') {
            // Only include for shipments over 150lb
            $totalWeight = 0;

            foreach ($rateRequest->RequestedShipment->RequestedPackageLineItems as $key => $packageLineItem) {
                $totalWeight += $packageLineItem->Weight->Value;
            }

            if ($totalWeight > 150) {
                $rateRequest->CarrierCodes = ['FXFR'];

                $rateRequest->RequestedShipment->Shipper->Address->StreetLines = [$this->getSetting('freightShipperStreetAddress'), $this->getSetting('freightShipperStreetAddress2')];
                $rateRequest->RequestedShipment->Shipper->Address->City = $this->getSetting('freightShipperCity');
                $rateRequest->RequestedShipment->Shipper->Address->PostalCode = $this->getSetting('freightShipperZipcode');
                $rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = $this->getSetting('freightShipperStateCode');
                $rateRequest->RequestedShipment->Shipper->Address->CountryCode = $this->getSetting('freightShipperCountryCode');

                $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = 'SENDER';
                $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->ResponsibleParty->AccountNumber = $this->getSetting('freightAccountNumber');

                $rateRequest->RequestedShipment->FreightShipmentDetail->Role = 'SHIPPER';
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightAccountNumber = $this->getSetting('freightAccountNumber');
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightBillingContactAndAddress->Address->StreetLines = [$this->getSetting('freightBillingStreetAddress'), $this->getSetting('freightBillingStreetAddress2')];
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightBillingContactAndAddress->Address->City = $this->getSetting('freightBillingCity');
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightBillingContactAndAddress->Address->PostalCode = $this->getSetting('freightBillingZipcode');
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightBillingContactAndAddress->Address->StateOrProvinceCode = $this->getSetting('freightBillingStateCode');
                $rateRequest->RequestedShipment->FreightShipmentDetail->FedExFreightBillingContactAndAddress->Address->CountryCode = $this->getSetting('freightBillingCountryCode');

                $lineItems = [];

                // Modify each line item to contain extra required info for freight
                foreach ($rateRequest->RequestedShipment->RequestedPackageLineItems as $key => &$packageLineItem) {
                    $packageLineItem->SequenceNumber = $key + 1;
                    $packageLineItem->PhysicalPackaging = 'SKID';
                    $packageLineItem->AssociatedFreightLineItems = [['Id' => $key + 1]];

                    // Create line items for freight
                    $lineItems[] = [
                        'Id' => $key + 1,
                        'FreightClass' => 'CLASS_050',
                        'Packaging' => 'SKID',
                        'Weight' => $packageLineItem->Weight,
                    ];
                }

                $rateRequest->RequestedShipment->FreightShipmentDetail->LineItems = $lineItems;
            }
        }

        $rateServiceRequest = new Request();

        // Check for test or production endpoint
        if ($this->getSetting('useTestEndpoint')) {
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

                    $rate = min(array_filter($ratedShipmentDetailRates));
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
        } else if (isset($rateReply->Notifications)) {
            foreach ($rateReply->Notifications as $message) {
                Provider::error($this, 'Rate Error: ' . $message->Message);
            }
        } else {
            Provider::error($this, Craft::t('postie', 'Unable to fetch rates: `{json}`.', [
                'json' => Json::encode($rateReply),
            ]));
        }
    }

    protected function fetchConnection(): bool
    {
        try {
            // Create test addresses
            $sender = TestingHelper::getTestAddress('US', ['city' => 'Cupertino']);
            $recipient = TestingHelper::getTestAddress('US', ['city' => 'Mountain View']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $rateRequest = new RateRequest();
            $rateRequest->WebAuthenticationDetail->UserCredential->Key = $this->getSetting('key');
            $rateRequest->WebAuthenticationDetail->UserCredential->Password = $this->getSetting('password');
            $rateRequest->ClientDetail->AccountNumber = $this->getSetting('accountNumber');
            $rateRequest->ClientDetail->MeterNumber = $this->getSetting('meterNumber');
            $rateRequest->Version->ServiceId = 'crs';
            $rateRequest->Version->Major = 24;
            $rateRequest->Version->Minor = 0;
            $rateRequest->Version->Intermediate = 0;
            $rateRequest->ReturnTransitAndCommit = true;
            $rateRequest->RequestedShipment->Shipper->Address->City = $sender->city;
            $rateRequest->RequestedShipment->Shipper->Address->PostalCode = $sender->zipCode;
            $rateRequest->RequestedShipment->Recipient->Address->City = $recipient->city;
            $rateRequest->RequestedShipment->Recipient->Address->PostalCode = $recipient->zipCode;

            $rateServiceRequest = new Request();
            $rateServiceRequest->getSoapClient()->__setLocation(Request::TESTING_URL);
            $rateReply = $rateServiceRequest->getGetRatesReply($rateRequest);
        } catch (\Throwable $e) {
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _createPackageLineItem($order, $packedBoxes)
    {
        $packages = [];

        foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
            // Assuming we pack all order line items into one package to save shipping costs we creating just one package line item
            $packageLineItem = new RequestedPackageLineItem();

            // Weight
            $packageLineItem->Weight->Units = $this->_getUnitOfMeasurement('weight');
            $packageLineItem->Weight->Value = $packedBox['weight'];

            // Dimensions
            $packageLineItem->Dimensions->Units = $this->_getUnitOfMeasurement('dimension');
            $packageLineItem->Dimensions->Length = $packedBox['length'];
            $packageLineItem->Dimensions->Width = $packedBox['width'];
            $packageLineItem->Dimensions->Height = $packedBox['height'];

            $packageLineItem->GroupPackageCount = 1;

            if ($this->getSetting('includeInsurance')) {
                $packageLineItem->InsuredValue->Currency = $order->paymentCurrency;
                $packageLineItem->InsuredValue->Amount = $packedBoxes->getTotalPrice();
            }

            $packages[] = $packageLineItem;
        }

        return $packages;
    }

    private function _getUnitOfMeasurement($type)
    {
        $units = [
            'lb' => WeightUnits::_LB,
            'kg' => WeightUnits::_KG,
            'in' => LinearUnits::_IN,
            'cm' => LinearUnits::_CM,
        ];

        if ($type === 'weight') {
            return $units[$this->weightUnit] ?? WeightUnits::_LB;
        }

        if ($type === 'dimension') {
            return $units[$this->dimensionUnit] ?? LinearUnits::_IN;
        }
    }
}
