<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use craft\commerce\Plugin as Commerce;

use Ups\Rate;
use Ups\Entity\Address;
use Ups\Entity\DeliveryConfirmation;
use Ups\Entity\Dimensions;
use Ups\Entity\InsuredValue;
use Ups\Entity\Package;
use Ups\Entity\PackagingType;
use Ups\Entity\PackageServiceOptions;
use Ups\Entity\PaymentInformation;
use Ups\Entity\PickupType;
use Ups\Entity\RateInformation;
use Ups\Entity\RateRequest;
use Ups\Entity\RateResponse;
use Ups\Entity\Service;
use Ups\Entity\ShipFrom;
use Ups\Entity\Shipment;
use Ups\Entity\Shipper;
use Ups\Entity\UnitOfMeasurement;
use Ups\Exception\InvalidResponseException;

class UPS extends Provider
{
    // Properties
    // =========================================================================

    public $handle = 'ups';
    public $weightUnit = 'lb';
    public $dimensionUnit = 'in';

    private $maxWeight = 68038.9; // 150lbs

    private $pickupCode = [
        '01' => 'Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '19' => 'Letter Center',
        '20' => 'Air Service Center',
    ];

    private $euCountries = [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'ES' => 'Spain',
        'FI' => 'Finland',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'GR' => 'Greece',
        'HU' => 'Hungary',
        'HR' => 'Croatia',
        'IE' => 'Ireland, Republic of (EIRE)',
        'IT' => 'Italy',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SE' => 'Sweden',
        'SI' => 'Slovenia',
        'SK' => 'Slovakia',
    ];


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'UPS');
    }

    public function getPickupTypeOptions()
    {
        $options = [];

        foreach ($this->pickupCode as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function getFreightPackingTypeOptions()
    {
        return [
            'BAG' => 'Bag',
            'BAL' => 'Bale',
            'BAR' => 'Barrel',
            'BDL' => 'Bundle',
            'BIN' => 'Bin',
            'BOX' => 'Box',
            'BSK' => 'Basket',
            'BUN' => 'Bunch',
            'CAB' => 'Cabinet',
            'CAN' => 'Can',
            'CAR' => 'Carrier',
            'CAS' => 'Case',
            'CBY' => 'CarBoy',
            'CON' => 'Container',
            'CRT' => 'Crate',
            'CSK' => 'Cask',
            'CTN' => 'Carton',
            'CYL' => 'Cylinder',
            'DRM' => 'Drum',
            'LOO' => 'Loose',
            'OTH' => 'Other',
            'PAL' => 'Pail',
            'PCS' => 'Pieces',
            'PKG' => 'Package',
            'PLN' => 'Pipe Line',
            'PLT' => 'Pallet',
            'RCK' => 'Rack',
            'REL' => 'Reel',
            'ROL' => 'Roll',
            'SKD' => 'Skid',
            'SPL' => 'Spool',
            'TBE' => 'Tube',
            'TNK' => 'Tank',
            'UNT' => 'Unit',
            'VPK' => 'Van Pack',
            'WRP' => 'Wrapped',
        ];
    }

    public function getFreightClassOptions()
    {
        return [
            '50' => '50',
            '55' => '55',
            '60' => '60',
            '65' => '65',
            '70' => '70',
            '77.5' => '77.5',
            '85' => '85',
            '92.5' => '92.5',
            '100' => '100',
            '110' => '110',
            '125' => '125',
            '150' => '150',
            '175' => '175',
            '200' => '200',
            '250' => '250',
            '300' => '300',
            '400' => '400',
            '500' => '500',
        ];
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

            'S_UPSTODAY_STANDARD' => 'UPS Today Standard',
            'S_UPSTODAY_DEDICATEDCOURIER' => 'UPS Today Dedicated Courier',
            'S_UPSTODAY_INTERCITY' => 'UPS Today Intercity',
            'S_UPSTODAY_EXPRESS' => 'UPS Today Express',
            'S_UPSTODAY_EXPRESSSAVER' => 'UPS Today Express Saver',
            'S_UPSWW_EXPRESSFREIGHT' => 'UPS Worldwide Express Freight',

            // Time in Transit Response Service Codes: United States Domestic Shipments
            'TT_S_US_AIR_1DAYAM' => 'UPS Next Day Air Early',
            'TT_S_US_AIR_1DAY' => 'UPS Next Day Air',
            'TT_S_US_AIR_SAVER' => 'UPS Next Day Air Saver',
            'TT_S_US_AIR_2DAYAM' => 'UPS Second Day Air A.M.',
            'TT_S_US_AIR_2DAY' => 'UPS Second Day Air',
            'TT_S_US_3DAYSELECT' => 'UPS Three-Day Select',
            'TT_S_US_GROUND' => 'UPS Ground',
            'TT_S_US_AIR_1DAYSATAM' => 'UPS Next Day Air Early (Saturday Delivery)',
            'TT_S_US_AIR_1DAYSAT' => 'UPS Next Day Air (Saturday Delivery)',
            'TT_S_US_AIR_2DAYSAT' => 'UPS Second Day Air (Saturday Delivery)',

            // Time in Transit Response Service Codes: Other Shipments Originating in US
            'TT_S_US_INTL_EXPRESSPLUS' => 'UPS Worldwide Express Plus',
            'TT_S_US_INTL_EXPRESS' => 'UPS Worldwide Express',
            'TT_S_US_INTL_SAVER' => 'UPS Worldwide Express Saver',
            'TT_S_US_INTL_STANDARD' => 'UPS Standard',
            'TT_S_US_INTL_EXPEDITED' => 'UPS Worldwide Expedited',

            // Time in Transit Response Service Codes: Shipments Originating in the EU
            // Destination is WITHIN the Origin Country
            'TT_S_EU_EXPRESSPLUS' => 'UPS Express Plus',
            'TT_S_EU_EXPRESS' => 'UPS Express',
            'TT_S_EU_SAVER' => 'UPS Express Saver',
            'TT_S_EU_STANDARD' => 'UPS Standard',

            // Time in Transit Response Service Codes: Shipments Originating in the EU
            // Destination is Another EU Country
            'TT_S_EU_TO_EU_EXPRESSPLUS' => 'UPS Express Plus',
            'TT_S_EU_TO_EU_EXPRESS' => 'UPS Express',
            'TT_S_EU_TO_EU_SAVER' => 'UPS Express Saver',
            'TT_S_EU_TO_EU_STANDARD' => 'UPS Standard',

            // Time in Transit Response Service Codes: Shipments Originating in the EU
            // Destination is Outside the EU
            'TT_S_EU_TO_OTHER_EXPRESS_NA1' => 'UPS Express NA 1',
            'TT_S_EU_TO_OTHER_EXPRESSPLUS' => 'UPS Worldwide Express Plus',
            'TT_S_EU_TO_OTHER_EXPRESS' => 'UPS Express',
            'TT_S_EU_TO_OTHER_SAVER' => 'UPS Express Saver',
            'TT_S_EU_TO_OTHER_EXPEDITED' => 'UPS Expedited',
            'TT_S_EU_TO_OTHER_STANDARD' => 'UPS Standard',
        ];
    }

    public static function defineDefaultBoxes()
    {
        return [
            [
                'id' => 'ups-1',
                'name' => 'UPS Letter',
                'boxLength' => 12.5,
                'boxWidth' => 9.5,
                'boxHeight' => 0.25,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'enabled' => true,
            ],
            [
                'id' => 'ups-2',
                'name' => 'Tube',
                'boxLength' => 38,
                'boxWidth' => 6,
                'boxHeight' => 6,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-3',
                'name' => '10KG Box',
                'boxLength' => 16.5,
                'boxWidth' => 13.25,
                'boxHeight' => 10.75,
                'boxWeight' => 0,
                'maxWeight' => 22,
                'enabled' => true,
            ],
            [
                'id' => 'ups-4',
                'name' => '25KG Box',
                'boxLength' => 19.75,
                'boxWidth' => 17.75,
                'boxHeight' => 13.2,
                'boxWeight' => 0,
                'maxWeight' => 55,
                'enabled' => true,
            ],
            [
                'id' => 'ups-5',
                'name' => 'Small Express Box',
                'boxLength' => 13,
                'boxWidth' => 11,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-6',
                'name' => 'Medium Express Box',
                'boxLength' => 16,
                'boxWidth' => 11,
                'boxHeight' => 3,
                'boxWeight' => 0,
                'maxWeight' => 100,
                'enabled' => true,
            ],
            [
                'id' => 'ups-7',
                'name' => 'Large Express Box',
                'boxLength' => 18,
                'boxWidth' => 13,
                'boxHeight' => 3,
                'boxWeight' => 0,
                'maxWeight' => 30,
                'enabled' => true,
            ],
        ];
    }

    public function getWeightUnitOptions()
    {
        return [
            [ 'label' => Craft::t('commerce', 'Kilograms (kg)'), 'value' => 'kg' ],
            [ 'label' => Craft::t('commerce', 'Pounds (lb)'), 'value' => 'lb' ],
        ];
    }

    public function getDimensionUnitOptions()
    {
        return [
            [ 'label' => Craft::t('commerce', 'Centimeters (cm)'), 'value' => 'cm' ],
            [ 'label' => Craft::t('commerce', 'Inches (in)'), 'value' => 'in' ],
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

        $client = $this->_getClient();

        if (!$client) {
            Provider::error($this, 'Unable to communicate with API.');
            return false;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order)->getSerializedPackedBoxList();

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

        // EU
        // $storeLocation = TestingHelper::getTestAddress('GB', ['city' => 'London']);
        // $order->shippingAddress = TestingHelper::getTestAddress('GB', ['city' => 'Dunchurch']);
        //
        // TESTING
        //



        // Check for using freight, we have to roll our own solution as `gabrielbull/php-ups-api`
        // doesn't support LTL rates. One of the reasons TODO our own client libraries.
        if ($this->getSetting('enableFreight')) {
            foreach ($packedBoxes as $packedBox) {
                return $this->fetchFreightRates($storeLocation, $packedBox, $order);
            }

            return;
        }

        try {
            $shipment = new Shipment();

            $shipFromAddress = new Address();
            $shipFromAddress->setPostalCode($storeLocation->zipCode);

            // UPS can't handle 3-character states. Ignoring it is valid for international order
            // But states are also required for US and Canada
            $allowedZipCodeCountries = ['US', 'CA'];

            if ($storeLocation->country) {
                $shipFromAddress->setCountryCode($storeLocation->country->iso);
                
                if (in_array($storeLocation->country->iso, $allowedZipCodeCountries)) {
                    $state = $storeLocation->state->abbreviation ?? '';

                    $shipFromAddress->setStateProvinceCode($state);
                }
            }

            $shipFrom = new ShipFrom();
            $shipFrom->setAddress($shipFromAddress);

            $shipment->setShipFrom($shipFrom);

            $shipTo = $shipment->getShipTo();
            $shipToAddress = $shipTo->getAddress();
            $shipToAddress->setPostalCode($order->shippingAddress->zipCode);

            if ($this->getSetting('residentialAddress')) {
                $shipToAddress->setResidentialAddressIndicator(true);
            }

            if ($order->shippingAddress->country) {
                $shipToAddress->setCountryCode($order->shippingAddress->country->iso);

                if (in_array($order->shippingAddress->country->iso, $allowedZipCodeCountries)) {
                    $state = $order->shippingAddress->state->abbreviation ?? '';

                    $shipToAddress->setStateProvinceCode($state);
                }
            }

            foreach ($packedBoxes as $packedBox) {
                $package = new Package();
                $package->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
                $package->getPackageWeight()->setWeight(round($packedBox['weight'], 2));

                if ($this->getSetting('requireSignature')) {
                    $deliveryConfirmation = new DeliveryConfirmation();

                    if ($requireSignature === 'required') {
                        $deliveryConfirmation->setDcisType(DeliveryConfirmation::DELIVERY_CONFIRMATION_SIGNATURE_REQUIRED);
                    } else if ($requireSignature === 'adult') {
                        $deliveryConfirmation->setDcisType(DeliveryConfirmation::DELIVERY_CONFIRMATION_ADULT_SIGNATURE_REQUIRED);
                    }

                    $package->getPackageServiceOptions()->setDeliveryConfirmation($deliveryConfirmation);
                }

                $weightUnit = new UnitOfMeasurement;
                $weightUnit->setCode($this->_getUnitOfMeasurement('weight'));
                $package->getPackageWeight()->setUnitOfMeasurement($weightUnit);

                $packageDimensions = new Dimensions();
                $packageDimensions->setHeight(round($packedBox['height'], 2));
                $packageDimensions->setWidth(round($packedBox['width'], 2));
                $packageDimensions->setLength(round($packedBox['length'], 2));

                $unit = new UnitOfMeasurement;
                $unit->setCode($this->_getUnitOfMeasurement('dimension'));

                $packageDimensions->setUnitOfMeasurement($unit);
                $package->setDimensions($packageDimensions);

                if ($this->getSetting('includeInsurance')) {
                    $insuredValue = new InsuredValue();
                    $insuredValue->setMonetaryValue((float)$order->total);
                    $insuredValue->setCurrencyCode($order->paymentCurrency);

                    $packageServiceOptions = new PackageServiceOptions();
                    $packageServiceOptions->setInsuredValue($insuredValue);

                    $package->setPackageServiceOptions($packageServiceOptions);
                }

                $shipment->addPackage($package);
            }

            // Check for negotiated rates
            if ($this->getSetting('negotiatedRates') && $accountNumber = $this->getSetting('accountNumber')) {
                $rateInformation = new RateInformation;
                $rateInformation->setNegotiatedRatesIndicator(1);
                $shipment->setRateInformation($rateInformation);

                $shipper = $shipment->getShipper();
                $shipper->setShipperNumber($accountNumber);
                $shipment->setPaymentInformation(new PaymentInformation('prepaid', (object)['AccountNumber' => $accountNumber]));
            }

            $rates = new RateResponse();

            $rateRequest = new RateRequest();
            $rateRequest->setShipment($shipment);

            $pickupCode = $this->getSetting('pickupType') ?? '01';

            $pickupType = new PickupType();
            $pickupType->setCode($pickupCode);
            $rateRequest->setPickupType($pickupType);

            $this->beforeSendPayload($this, $rateRequest, $order);

            // Perform the request
            $rates = $this->_client->shopRates($rateRequest);

            // Check for Sure Post rates - must be a separate request
            $surePost = $this->services['S_SURE_POST']->enabled ?? false;
            
            if ($surePost) {
                $service = new Service;
                $service->setCode(Service::S_SURE_POST);
                $service->setDescription($service->getName());
                $shipment->setService($service);

                // If SurePost shipping dimensions are exceeded, an exception is thrown. We'll catch it, log it,
                // and make sure SurePost is not a valid shipping method in this situation.
                $surePostRate = null;
                try {
                    $surePostRate = $this->_client->getRate($shipment);
                } catch (InvalidResponseException $e) {
                    Provider::error($this, 'SurePost API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
                }

                // Attach Sure Post rates into any other rates
                if ($surePostRate) {
                    if (!isset($rates->RatedShipment) || !is_array($rates->RatedShipment)) {
                        $rates->RatedShipment = [];
                    }

                    $rates->RatedShipment = array_merge($rates->RatedShipment, $surePostRate->RatedShipment);
                }
            }

            foreach ($rates->RatedShipment as $rate) {
                $serviceHandle = $this->_getServiceHandle($rate->Service->getCode(), $storeLocation, $order->shippingAddress);

                if (!$serviceHandle) {
                    Provider::error($this, 'Unable to find matching service handle for: `' . $rate->Service->getName() . ':' . '`.');

                    continue;
                }

                $rateInfo = [
                    'amount' => $rate->TotalCharges->MonetaryValue ?? '',
                    'options' => [
                        'guaranteedDaysToDelivery' => $rate->GuaranteedDaysToDelivery ?? '',
                        'scheduledDeliveryTime' => $rate->ScheduledDeliveryTime ?? '',
                        'rateShipmentWarning' => $rate->RateShipmentWarning ?? '',
                        'surCharges' => $rate->SurCharges ?? '',
                        'timeInTransit' => $rate->TimeInTransit ?? '',
                    ],
                ];

                // If we're using negotiated rates, return that, not the normal values
                $negotiatedRates = $rate->NegotiatedRates ?? '';

                if ($negotiatedRates) {
                    $rateInfo['amount'] = $rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue ?? '';
                }

                $this->_rates[$serviceHandle] = $rateInfo;
            }

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'response' => $rates,
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
            $shipment = new Shipment();
            $shipFromAddress = new Address();
            $shipFromAddress->setPostalCode($sender->zipCode);

            $shipFrom = new ShipFrom();
            $shipFrom->setAddress($shipFromAddress);
            $shipment->setShipFrom($shipFrom);

            $shipTo = $shipment->getShipTo();
            $shipToAddress = $shipTo->getAddress();
            $shipToAddress->setPostalCode($recipient->zipCode);

            $package = new Package();
            $package->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
            $package->getPackageWeight()->setWeight(round($packedBox['weight'], 2));
            $weightUnit = new UnitOfMeasurement;
            $weightUnit->setCode(UnitOfMeasurement::UOM_LBS);
            $package->getPackageWeight()->setUnitOfMeasurement($weightUnit);

            $packageDimensions = new Dimensions();
            $packageDimensions->setHeight(round($packedBox['height'], 2));
            $packageDimensions->setWidth(round($packedBox['width'], 2));
            $packageDimensions->setLength(round($packedBox['length'], 2));

            $unit = new UnitOfMeasurement;
            $unit->setCode(UnitOfMeasurement::UOM_IN);

            $packageDimensions->setUnitOfMeasurement($unit);
            $package->setDimensions($packageDimensions);
            $shipment->addPackage($package);

            $rateRequest = new RateRequest();
            $rateRequest->setShipment($shipment);
            $rates = $this->_getClient()->shopRates($rateRequest);
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

    public function fetchFreightRates($storeLocation, $packedBox, $order)
    {
        try {
            $accountNumber = $this->getSetting('accountNumber');
            $freightClass = $this->getSetting('freightClass');
            $freightPackingType = $this->getSetting('freightPackingType');

            // UPS Freight LTL
            $freightService = '308';

            $shipFrom = [];
            $shipFrom['Name'] = $this->getSetting('freightShipperName');
            $shipFrom['EMailAddress'] = $this->getSetting('freightShipperEmail');
            $shipFrom['Address']['AddressLine'] = $storeLocation->address1;
            $shipFrom['Address']['City'] = $storeLocation->city;
            $shipFrom['Address']['PostalCode'] = $storeLocation->zipCode;

            // UPS can't handle 3-character states. Ignoring it is valid for international order
            // But states are also required for US and Canada
            $allowedZipCodeCountries = ['US', 'CA'];

            if ($storeLocation->country) {
                $shipFrom['Address']['CountryCode'] = $storeLocation->country->iso;
                
                if (in_array($storeLocation->country->iso, $allowedZipCodeCountries)) {
                    $state = $storeLocation->state->abbreviation ?? '';

                    $shipFrom['Address']['StateProvinceCode'] = $state;
                }
            }

            $shipTo = [];
            $shipTo['Address']['City'] = $order->shippingAddress->city;
            $shipTo['Address']['PostalCode'] = $order->shippingAddress->zipCode;

            if ($order->shippingAddress->country) {
                $shipTo['Address']['CountryCode'] = $order->shippingAddress->country->iso;

                if (in_array($order->shippingAddress->country->iso, $allowedZipCodeCountries)) {
                    $state = $order->shippingAddress->state->abbreviation ?? '';

                    $shipTo['Address']['StateProvinceCode'] = $state;
                }
            }

            $payload = [
                'FreightRateRequest' => [
                    'ShipperNumber' => $accountNumber,
                    'ShipFrom' => $shipFrom,
                    'ShipTo' => $shipTo,
                    'PaymentInformation' => [
                        'Payer' => array_merge($shipFrom, [
                            'ShipperNumber' => $accountNumber,
                        ]),
                        'ShipmentBillingOption' => [
                            'Code' => '10',
                        ],
                    ],
                    'Service' => [
                        'Code' => $freightService,
                    ],
                    'Commodity' => [
                        'Description' => 'FRS-Freight',
                        'Weight' =>  [
                            'Value' => round((string)$packedBox['weight'], 2),
                            'UnitOfMeasurement' => [
                                'Code' => $this->_getUnitOfMeasurement('weight'),
                            ],
                        ],
                        'Dimensions' => [
                            'Length' => round((string)$packedBox['length'], 2),
                            'Width' => round((string)$packedBox['width'], 2),
                            'Height' => round((string)$packedBox['height'], 2),
                            'UnitOfMeasurement' => [
                                'Code' => $this->_getUnitOfMeasurement('dimension'),
                            ],
                        ],
                        'FreightClass' => $freightClass,
                        'NumberOfPieces' => '1',
                        'PackagingType' => [
                            'Code' => $freightPackingType,
                        ],
                    ],
                    'AlternateRateOptions' => [
                        'Code' => '3',
                    ],
                    'PickupRequest' => [
                        'PickupDate' => date('Ymd'),
                    ],
                    'GFPOptions' => [
                        'GPFAccesorialRateIndicator' => '',
                    ],
                    'TimeInTransitIndicator' => '',
                ],
            ];

            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                $accessKey = $this->getSetting('testApiKey');
                $endpoint = 'https://wwwcie.ups.com/ship/v1/freight/rating/ground';
            } else {
                $accessKey = $this->getSetting('apiKey');
                $endpoint = 'https://onlinetools.ups.com/ship/v1/freight/rating/ground';
            }

            $client = Craft::createGuzzleClient([
                'headers' => [
                    'AccessLicenseNumber' => $accessKey,
                    'content-type' => 'application/json',
                    'password' => $this->getSetting('password'),
                    'username' => $this->getSetting('username'),
                ],
            ]);

            $response = $client->request('POST', $endpoint, ['json' => $payload]);
            $json = Json::decode((string)$response->getBody());

            $handle = 'freight-' . $freightClass;

            $this->_rates[$handle] = [
                'amount' => $json['FreightRateResponse']['TotalShipmentCharge']['MonetaryValue'] ?? '',
            ];

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'response' => $json,
                'order' => $order,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;

            // Because this isn't known in advanced, and only ever one rate, create the service dynamically
            $shippingMethod = new ShippingMethod();
            $shippingMethod->handle = $handle;
            $shippingMethod->provider = $this;
            $shippingMethod->name = 'UPS Freight LTL';
            $shippingMethod->enabled = true;

            $this->services[$handle] = $shippingMethod;
        } catch (\Throwable $e) {
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                $accessKey = $this->getSetting('testApiKey');
            } else {
                $accessKey = $this->getSetting('apiKey');
            }

            $userId = $this->getSetting('username');
            $password = $this->getSetting('password');

            $this->_client = new Rate($accessKey, $userId, $password);
        }

        return $this->_client;
    }

    private function _inEU($country)
    {
        return isset($this->euCountries[$country->iso]) ? true : false;
    }

    private function _getServiceHandle($code, $storeLocation, $shippingAddress)
    {
        // We need some smarts here, because UPS has multiple handles for the same service code, depending on the
        // origin or destination of the parcel. Do a little more work here...
        $services = [
            'S_AIR_1DAYEARLYAM' => '14',
            'S_AIR_1DAY' => '01',
            'S_AIR_1DAYSAVER' => '13',
            'S_AIR_2DAYAM' => '59',
            'S_AIR_2DAY' => '02',
            'S_3DAYSELECT' => '12',
            'S_GROUND' => '03',
            'S_SURE_POST' => '93',

            // Valid international values
            'S_STANDARD' => '11',
            'S_WW_EXPRESS' => '07',
            'S_WW_EXPRESSPLUS' => '54',
            'S_WW_EXPEDITED' => '08',
            'S_SAVER' => '65',
            'S_ACCESS_POINT' => '70',

            // Valid Poland to Poland same day values
            'S_UPSTODAY_STANDARD' => '82',
            'S_UPSTODAY_DEDICATEDCOURIER' => '83',
            'S_UPSTODAY_INTERCITY' => '84',
            'S_UPSTODAY_EXPRESS' => '85',
            'S_UPSTODAY_EXPRESSSAVER' => '86',
            'S_UPSWW_EXPRESSFREIGHT' => '96',

            // Valid Germany to Germany values
            'S_UPSEXPRESS_1200' => '74',

            // Time in Transit Response Service Codes: United States Domestic Shipments
            'TT_S_US_AIR_1DAYAM' => '1DM',
            'TT_S_US_AIR_1DAY' => '1DA',
            'TT_S_US_AIR_SAVER' => '1DP',
            'TT_S_US_AIR_2DAYAM' => '2DM',
            'TT_S_US_AIR_2DAY' => '2DA',
            'TT_S_US_3DAYSELECT' => '3DS',
            'TT_S_US_GROUND' => 'GND',
            'TT_S_US_AIR_1DAYSATAM' => '1DMS',
            'TT_S_US_AIR_1DAYSAT' => '1DAS',
            'TT_S_US_AIR_2DAYSAT' => '2DAS',
        ];

        // Comment these out until we can figure out a better way to test origin EU addresses

        // $services = [
        //     // Time in Transit Response Service Codes: Other Shipments Originating in US
        //     'TT_S_US_INTL_EXPRESSPLUS' => '21',
        //     'TT_S_US_INTL_EXPRESS' => '01',
        //     'TT_S_US_INTL_SAVER' => '28',
        //     'TT_S_US_INTL_STANDARD' => '03',
        //     'TT_S_US_INTL_EXPEDITED' => '05',
        // ];

        // $services = [
        //     // Time in Transit Response Service Codes: Shipments Originating in the EU
        //     // Destination is WITHIN the Origin Country
        //     'TT_S_EU_EXPRESSPLUS' => '23',
        //     'TT_S_EU_EXPRESS' => '24',
        //     'TT_S_EU_SAVER' => '26',
        //     'TT_S_EU_STANDARD' => '25',
        // ];

        // $services = [
        //     // Time in Transit Response Service Codes: Shipments Originating in the EU
        //     // Destination is Another EU Country
        //     'TT_S_EU_TO_EU_EXPRESSPLUS' => '22',
        //     'TT_S_EU_TO_EU_EXPRESS' => '10',
        //     'TT_S_EU_TO_EU_SAVER' => '18',
        //     'TT_S_EU_TO_EU_STANDARD' => '08',
        // ];

        // $services = [
        //     // Time in Transit Response Service Codes: Shipments Originating in the EU
        //     // Destination is Outside the EU
        //     'TT_S_EU_TO_OTHER_EXPRESS_NA1' => '11',
        //     'TT_S_EU_TO_OTHER_EXPRESSPLUS' => '21',
        //     'TT_S_EU_TO_OTHER_EXPRESS' => '01',
        //     'TT_S_EU_TO_OTHER_SAVER' => '28',
        //     'TT_S_EU_TO_OTHER_EXPEDITED' => '05',
        //     'TT_S_EU_TO_OTHER_STANDARD' => '68',
        // ];

        $serviceHandle = array_search($code, $services);

        return $serviceHandle;
    }

    private function _getUnitOfMeasurement($type)
    {
        $units = [
            'lb' => UnitOfMeasurement::UOM_LBS,
            'kg' => UnitOfMeasurement::UOM_KGS,
            'in' => UnitOfMeasurement::UOM_IN,
            'cm' => UnitOfMeasurement::UOM_CM,
        ];

        if ($type === 'weight') {
            return $units[$this->weightUnit] ?? UnitOfMeasurement::UOM_LBS;
        }

        if ($type === 'dimension') {
            return $units[$this->dimensionUnit] ?? UnitOfMeasurement::UOM_IN;
        }
    }
}
