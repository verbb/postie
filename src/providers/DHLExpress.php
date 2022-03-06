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
    // Properties
    // =========================================================================

    public ?string $handle = 'dhlExpress';
    public string $weightUnit = 'kg';
    public string $dimensionUnit = 'cm';

    private int $maxWeight = 70000; // 70kg


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'DHL Express');
    }

    
    // Public Methods
    // =========================================================================

    public function supportsDynamicServices(): bool
    {
        return true;
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

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        //
        // TESTING
        //
        // $country = Commerce::getInstance()->countries->getCountryByIso('DE');
        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'DE');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->city = 'Berlin';
        // $storeLocation->zipCode = '12345';
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->city = 'Berlin';
        // $order->shippingAddress->zipCode = '12345';
        // $order->shippingAddress->countryId = $country->id;

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
                                'City' => $storeLocation->city ?? '',
                                'PostalCode' => $storeLocation->zipCode ?? '',
                                'CountryCode' => $storeLocation->country->iso ?? '',
                            ],
                            'Recipient' => [
                                'City' => $order->shippingAddress->city ?? '',
                                'PostalCode' => $order->shippingAddress->zipCode ?? '',
                                'CountryCode' => $order->shippingAddress->country->iso ?? '',
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
            $sender = TestingHelper::getTestAddress('AU', ['state' => 'VIC']);
            $recipient = TestingHelper::getTestAddress('AU', ['state' => 'TAS']);

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
                                'City' => $sender->city ?? '',
                                'PostalCode' => $sender->zipCode ?? '',
                                'CountryCode' => $sender->country->iso ?? '',
                            ],
                            'Recipient' => [
                                'City' => $recipient->city ?? '',
                                'PostalCode' => $recipient->zipCode ?? '',
                                'CountryCode' => $recipient->country->iso ?? '',
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
