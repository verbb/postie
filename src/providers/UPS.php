<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use GuzzleHttp\Client;

use Throwable;

class UPS extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'UPS');
    }

    public static function defineDefaultBoxes(): array
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

    public static function getServiceList(): array
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


    // Properties
    // =========================================================================

    public ?string $handle = 'ups';
    public string $dimensionUnit = 'in';
    public string $weightUnit = 'lb';

    private array $euCountries = [
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

    private float $maxWeight = 68038.9; // 150lbs

    private array $pickupCode = [
        '01' => 'Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '19' => 'Letter Center',
        '20' => 'Air Service Center',
    ];


    // Public Methods
    // =========================================================================

    public function getPickupTypeOptions(): array
    {
        $options = [];

        foreach ($this->pickupCode as $key => $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    public function getFreightPackingTypeOptions(): array
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

    public function getFreightClassOptions(): array
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

    public function getWeightUnitOptions(): array
    {
        return [
            ['label' => Craft::t('commerce', 'Kilograms (kg)'), 'value' => 'kg'],
            ['label' => Craft::t('commerce', 'Pounds (lb)'), 'value' => 'lb'],
        ];
    }

    public function getDimensionUnitOptions(): array
    {
        return [
            ['label' => Craft::t('commerce', 'Centimeters (cm)'), 'value' => 'cm'],
            ['label' => Craft::t('commerce', 'Inches (in)'), 'value' => 'in'],
        ];
    }

    public function getMaxPackageWeight($order): ?int
    {
        return $this->maxWeight;
    }

    public function fetchShippingRates($order): ?array
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $client = $this->_getClient();

        if (!$client) {
            Provider::error($this, 'Unable to communicate with API.');
            return null;
        }

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        //
        // TESTING
        //
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('US', ['locality' => 'Cupertino']);
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['locality' => 'Mountain View'], $order);

        // Canada
        // $storeLocation = TestingHelper::getTestAddress('CA', ['locality' => 'Toronto']);
        // $order->shippingAddress = TestingHelper::getTestAddress('CA', ['locality' => 'Montreal'], $order);

        // EU
        // $storeLocation = TestingHelper::getTestAddress('GB', ['locality' => 'London']);
        // $order->shippingAddress = TestingHelper::getTestAddress('GB', ['locality' => 'Dunchurch'], $order);
        //
        // TESTING
        //

        try {
            $weightUnit = $storeLocation->countryCode === 'US' ? 'LBS' : 'KGS';
            $dimensionUnit = $storeLocation->countryCode === 'US' ? 'IN' : 'CM';

            $payload = [
                'RateRequest' => [
                    'Shipment' => [
                        'Shipper' => [
                            'ShipperNumber' => $this->getSetting('accountNumber'),
                            'Address' => [
                                'City' => $storeLocation->locality ?? '',
                                'StateProvinceCode' => $storeLocation->administrativeArea ?? '',
                                'PostalCode' => $storeLocation->postalCode ?? '',
                                'CountryCode' => $storeLocation->countryCode ?? '',
                            ],
                        ],
                        'ShipFrom' => [
                            'Address' => [
                                'City' => $storeLocation->locality ?? '',
                                'StateProvinceCode' => $storeLocation->administrativeArea ?? '',
                                'PostalCode' => $storeLocation->postalCode ?? '',
                                'CountryCode' => $storeLocation->countryCode ?? '',
                            ],
                        ],
                        'ShipTo' => [
                            'Address' => [
                                'City' => $order->shippingAddress->locality ?? '',
                                'StateProvinceCode' => $order->shippingAddress->administrativeArea ?? '',
                                'PostalCode' => $order->shippingAddress->postalCode ?? '',
                                'CountryCode' => $order->shippingAddress->countryCode ?? '',
                            ],
                        ],
                    ],
                ],
            ];

            // Check for negotiated rates
            if ($this->getSetting('negotiatedRates')) {
                $payload['RateRequest']['Shipment']['ShipmentRatingOptions'] = [
                    'TPFCNegotiatedRatesIndicator' => 'Y',
                    'NegotiatedRatesIndicator' => 'Y',
                ];

                $payload['RateRequest']['Shipment']['PaymentDetails'] = [
                    'ShipmentCharge' => [
                        'Type' => '01',
                        'BillShipper' => [
                            'AccountNumber' => $this->getSetting('accountNumber'),
                        ],
                    ],
                ];
            }

            foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                $payload['RateRequest']['Shipment']['Package'][] = [
                    'PackagingType' => [
                        'Code' => '02',
                    ],
                    'Dimensions' => [
                        'UnitOfMeasurement' => [
                            'Code' => $dimensionUnit,
                        ],
                        'Length' => (string)round($packedBox['length'], 2),
                        'Width' => (string)round($packedBox['width'], 2),
                        'Height' => (string)round($packedBox['height'], 2),
                    ],
                    'PackageWeight' => [
                        'UnitOfMeasurement' => [
                            'Code' => $weightUnit,
                        ],
                        'Weight' => (string)round($packedBox['weight'], 2),
                    ],
                ];
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'api/rating/v1/Shop', [
                'json' => $payload,
            ]);

            foreach (ArrayHelper::getValue($response, 'RateResponse.RatedShipment', []) as $shippingRate) {
                $serviceName = ArrayHelper::getValue($shippingRate, 'Service.Name');
                $serviceHandle = ArrayHelper::getValue($shippingRate, 'Service.Code');
                $serviceHandle = $this->_getServiceHandle($serviceHandle, $storeLocation, $order->shippingAddress);

                if (!$serviceHandle) {
                    Provider::error($this, 'Unable to find matching service handle for: `' . $serviceName . '`.');

                    continue;
                }

                // Negotiated rates will be different to regular rates
                $rate = ArrayHelper::getValue($shippingRate, 'NegotiatedRateCharges.TotalCharge.MonetaryValue', ArrayHelper::getValue($shippingRate, 'TotalCharges.MonetaryValue'));

                $this->_rates[$serviceHandle] = [
                    'amount' => $rate,
                ];
            }

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'response' => $response,
                'order' => $order,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;
        } catch (Throwable $e) {
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
            $sender = TestingHelper::getTestAddress('US', ['locality' => 'Cupertino']);
            $recipient = TestingHelper::getTestAddress('US', ['locality' => 'Mountain View']);

            $weightUnit = $sender->countryCode === 'US' ? 'lb' : 'kg';
            $dimensionUnit = $sender->countryCode === 'US' ? 'in' : 'cm';

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($dimensionUnit, $weightUnit);
            $packedBox = $packedBoxes[0];

            // API needs this to be specific
            $weightUnit = $sender->countryCode === 'US' ? 'LBS' : 'KGS';
            $dimensionUnit = $sender->countryCode === 'US' ? 'IN' : 'CM';

            $payload = [
                'RateRequest' => [
                    'Shipment' => [
                        'Shipper' => [
                            'Address' => [
                                'City' => $sender->locality ?? '',
                                'PostalCode' => $sender->postalCode ?? '',
                                'CountryCode' => $sender->countryCode ?? '',
                            ],
                        ],
                        'ShipFrom' => [
                            'Address' => [
                                'City' => $sender->locality ?? '',
                                'PostalCode' => $sender->postalCode ?? '',
                                'CountryCode' => $sender->countryCode ?? '',
                            ],
                        ],
                        'ShipTo' => [
                            'Address' => [
                                'City' => $recipient->locality ?? '',
                                'PostalCode' => $recipient->postalCode ?? '',
                                'CountryCode' => $recipient->countryCode ?? '',
                            ],
                        ],
                        'Package' => [
                            [
                                'PackagingType' => [
                                    'Code' => '02',
                                ],
                                'Dimensions' => [
                                    'UnitOfMeasurement' => [
                                        'Code' => $dimensionUnit,
                                    ],
                                    'Length' => (string)round($packedBox['length'], 2),
                                    'Width' => (string)round($packedBox['width'], 2),
                                    'Height' => (string)round($packedBox['height'], 2),
                                ],
                                'PackageWeight' => [
                                    'UnitOfMeasurement' => [
                                        'Code' => $weightUnit,
                                    ],
                                    'Weight' => (string)round($packedBox['weight'], 2),
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->_request('POST', 'api/rating/v1/Shop', [
                'json' => $payload,
            ]);
        } catch (Throwable $e) {
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

    private function _getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $url = 'https://onlinetools.ups.com/';

        if ($this->getSetting('useTestEndpoint')) {
            $url = 'https://wwwcie.ups.com/';
        }

        // Fetch an access token first
        $authResponse = Json::decode((string)Craft::createGuzzleClient()
            ->request('POST', $url . 'security/v1/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'x-merchant-id' => $this->getSetting('clientId'),
                ],
                'auth' => [
                    $this->getSetting('clientId'),
                    $this->getSetting('clientSecret'),
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ])->getBody());

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Bearer ' . $authResponse['access_token'] ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    private function _inEU($country): bool
    {
        return isset($this->euCountries[$countryCode]);
    }

    private function _getServiceHandle($code, $storeLocation, $shippingAddress): bool|string
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

        return array_search($code, $services);
    }

    private function _getUnitOfMeasurement($type): string
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

        return '';
    }
}
