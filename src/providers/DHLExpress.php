<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use GuzzleHttp\Client;

use DateTime;
use Throwable;

class DHLExpress extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'DHL Express');
    }

    public static function supportsDynamicServices(): bool
    {
        return true;
    }


    // Properties
    // =========================================================================

    public ?string $handle = 'dhlExpress';
    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg';

    private int $maxWeight = 70000; // 70kg


    // Public Methods
    // =========================================================================

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

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        //
        // TESTING
        //
        // $storeLocation = TestingHelper::getTestAddress('DE', ['locality' => 'Berlin']);
        // $order->shippingAddress = TestingHelper::getTestAddress('DE', ['locality' => 'München'], $order);
        // $storeLocation = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
        // $order->shippingAddress = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS'], $order);
        // $sender = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
        // $recipient = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS']);

        // $country = Commerce::getInstance()->countries->getCountryByIso('US');
        // $administrativeArea = Commerce::getInstance()->administrativeAreas->getadministrativeAreaByAbbreviation($country->id, 'CA');

        // $storeLocation = new craft\elements\Address();
        // $storeLocation->addressLine1 = 'One Infinite Loop';
        // $storeLocation->locality = 'Cupertino';
        // $storeLocation->postalCode = '95014';
        // $storeLocation->administrativeAreaId = $administrativeArea->id;
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->addressLine1 = '1600 Amphitheatre Parkway';
        // $order->shippingAddress->locality = 'Mountain View';
        // $order->shippingAddress->postalCode = '94043';
        // $order->shippingAddress->administrativeAreaId = $administrativeArea->id;
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            // Set the ship date/time
            $shipDate = $this->getSetting('shipDate');
            $shipTime = $this->getSetting('shipTime.time');

            if ($shipDate === 'nextDay') {
                $shipDate = (new DateTime())->modify('+1 day')->format('Y-m-d');
            }

            if ($shipDate === 'nextBusinessDay') {
                $shipDate = (new DateTime())->modify('+1 weekday')->format('Y-m-d');
            }

            $shipTimestamp = (new DateTime($shipDate . ' ' . $shipTime))->format('Y-m-d\TH:i:s \G\M\TP');

            $payload = [
                'RateRequest' => [
                    'ClientDetails' => '',
                    'RequestedShipment' => [
                        'DropOffType' => 'REGULAR_PICKUP',
                        'ShipTimestamp' => $shipTimestamp,
                        'UnitOfMeasurement' => 'SI',
                        'Content' => 'NON_DOCUMENTS',
                        'DeclaredValue' => $packedBoxes->getTotalPrice(),
                        'DeclaredValueCurrecyCode' => $order->currency,
                        'PaymentInfo' => 'DAP',
                        'Account' => $this->getSetting('account'),

                        'Ship' => [
                            'Shipper' => [
                                'City' => $storeLocation->locality ?? '',
                                'PostalCode' => $storeLocation->postalCode ?? '',
                                'CountryCode' => $storeLocation->countryCode ?? '',
                            ],
                            'Recipient' => [
                                'City' => $order->shippingAddress->locality ?? '',
                                'PostalCode' => $order->shippingAddress->postalCode ?? '',
                                'CountryCode' => $order->shippingAddress->countryCode ?? '',
                            ],
                        ],
                        'Packages' => [],
                    ],
                ],
            ];

            foreach ($packedBoxes->getSerializedPackedBoxList() as $i => $packedBox) {
                $payload['RateRequest']['RequestedShipment']['Packages']['RequestedPackages'][] = [
                    '@number' => ($i + 1),
                    'Weight' => [
                        'Value' => $packedBox['weight'],
                    ],
                    'Dimensions' => [
                        'Length' => $packedBox['length'],
                        'Width' => $packedBox['width'],
                        'Height' => $packedBox['height'],
                    ],
                ];
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'RateRequest', [
                'json' => $payload,
            ]);

            $services = $response['RateResponse']['Provider'][0]['Service'] ?? [];

            // Check this is a correct array
            if (!isset($services[0])) {
                $services = [$services];
            }

            foreach ($services as $service) {
                $name = $service['Charges']['Charge'][0]['ChargeType'] ?? 'GENERAL';
                $amount = (float)($service['TotalNet']['Amount'] ?? 0);

                if ($amount) {
                    $this->_rates[$name] = [
                        'amount' => $amount,
                        'options' => $service,
                    ];
                }
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

            if (!$this->_rates) {
                Provider::error($this, Craft::t('postie', 'No available rates: `{json}`.', [
                    'json' => Json::encode($response),
                ]));
            }
        } catch (Throwable $e) {
            if (method_exists($e, 'hasResponse')) {
                $data = Json::decode((string)$e->getResponse()->getBody());
                $message = $data['error']['errorMessage'] ?? $e->getMessage();

                Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                    'message' => $message,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            } else {
                Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
        }

        return $this->_rates;
    }

    protected function fetchConnection(): bool
    {
        try {
            // Create test addresses
            $sender = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
            $recipient = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'RateRequest' => [
                    'ClientDetails' => '',
                    'RequestedShipment' => [
                        'DropOffType' => 'REGULAR_PICKUP',
                        'UnitOfMeasurement' => 'SI',
                        'Content' => 'NON_DOCUMENTS',
                        'PaymentInfo' => 'DAP',
                        'Ship' => [
                            'Shipper' => [
                                'City' => $sender->locality ?? '',
                                'PostalCode' => $sender->postalCode ?? '',
                                'CountryCode' => $sender->countryCode ?? '',
                            ],
                            'Recipient' => [
                                'City' => $recipient->locality ?? '',
                                'PostalCode' => $recipient->postalCode ?? '',
                                'CountryCode' => $recipient->countryCode ?? '',
                            ],
                        ],
                        'Packages' => [
                            'RequestedPackages' => [
                                '@number' => 1,
                                'Weight' => [
                                    'Value' => $packedBox['weight'],
                                ],
                                'Dimensions' => [
                                    'Length' => $packedBox['length'],
                                    'Width' => $packedBox['width'],
                                    'Height' => $packedBox['height'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->_request('POST', 'RateRequest', [
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

        $url = 'https://wsbexpress.dhl.com/rest/gbl/';

        if ($this->getSetting('useTestEndpoint')) {
            $url = 'https://wsbexpress.dhl.com/rest/sndpt/';
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => $url,
            'auth' => [
                $this->getSetting('username'), $this->getSetting('password'),
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        return Json::decode((string)$response->getBody());
    }
}
