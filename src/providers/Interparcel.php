<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use GuzzleHttp\Client;

use Throwable;

class Interparcel extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Interparcel');
    }

    public static function supportsDynamicServices(): bool
    {
        return true;
    }


    // Properties
    // =========================================================================

    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg';


    // Public Methods
    // =========================================================================

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
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
        // $order->shippingAddress = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS'], $order);

        // International
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['administrativeArea' => 'CA'], $order);
        //
        // 
        //

        try {
            $response = [];

            $payload = [
                'collection' => [
                    'city' => $storeLocation->locality ?? '',
                    'postcode' => $storeLocation->postalCode ?? '',
                    'state' => $storeLocation->administrativeArea ?? '',
                    'country' => $storeLocation->countryCode ?? '',
                ],
                'delivery' => [
                    'city' => $order->shippingAddress->locality ?? '',
                    'postcode' => $order->shippingAddress->postalCode ?? '',
                    'state' => $order->shippingAddress->administrativeArea ?? '',
                    'country' => $order->shippingAddress->countryCode ?? '',
                ],
                'parcels' => [],
            ];

            foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                $payload['parcels'][] = [
                    'weight' => $packedBox['weight'],
                    'length' => $packedBox['length'],
                    'width' => $packedBox['width'],
                    'height' => $packedBox['height'],
                ];
            }

            $carriers = $this->getSetting('carriers');
            $serviceLevels = $this->getSetting('serviceLevels');
            $pickupTypes = $this->getSetting('pickupTypes');

            if ($carriers && $carriers !== '*') {
                $payload['filter']['carriers'] = $carriers;
            }

            if ($serviceLevels && $serviceLevels !== '*') {
                $payload['filter']['serviceLevel'] = $serviceLevels;
            }

            if ($pickupTypes && $pickupTypes !== '*') {
                $payload['filter']['pickupType'] = $pickupTypes;
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'quote/v2', [
                'json' => ['shipment' => $payload],
            ]);

            $services = $response['services'] ?? [];

            if ($services) {
                foreach ($services as $service) {
                    $this->_rates[$service['service']] = [
                        'amount' => (float)($service['price'] ?? 0),
                        'options' => $service,
                    ];
                }
            } else {
                Provider::log($this, Craft::t('postie', 'No services found: `{json}`.', [
                    'json' => Json::encode($response),
                ]));
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
                'collection' => [
                    'city' => $sender->locality ?? '',
                    'postcode' => $sender->postalCode ?? '',
                    'state' => $sender->administrativeArea ?? '',
                    'country' => $sender->countryCode ?? '',
                ],
                'delivery' => [
                    'city' => $recipient->locality ?? '',
                    'postcode' => $recipient->postalCode ?? '',
                    'state' => $recipient->administrativeArea ?? '',
                    'country' => $recipient->countryCode ?? '',
                ],
                'parcels' => [
                    [
                        'weight' => $packedBox['weight'],
                        'length' => $packedBox['length'],
                        'width' => $packedBox['width'],
                        'height' => $packedBox['height'],
                    ],
                ],
            ];

            $response = $this->_request('POST', 'quote/v2', [
                'json' => ['shipment' => $payload],
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.au.interparcel.com/',
            'headers' => [
                'X-Interparcel-Auth' => $this->getSetting('apiKey'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

}
