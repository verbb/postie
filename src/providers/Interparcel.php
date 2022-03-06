<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

class Interparcel extends Provider
{
    // Properties
    // =========================================================================

    public string $weightUnit = 'kg';
    public string $dimensionUnit = 'cm';

    
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Interparcel');
    }

    
    // Public Methods
    // =========================================================================

    public function supportsDynamicServices(): bool
    {
        return true;
    }

    public function fetchShippingRates($order): array
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
        // $storeLocation = TestingHelper::getTestAddress('AU', ['state' => 'VIC']);
        // $order->shippingAddress = TestingHelper::getTestAddress('AU', ['state' => 'TAS']);

        // International
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['state' => 'CA']);
        //
        // 
        //

        try {
            $response = [];

            $payload = [
                'collection' => [
                    'city' => $storeLocation->city ?? '',
                    'postcode' => $storeLocation->zipCode ?? '',
                    'state' => $storeLocation->state->abbreviation ?? '',
                    'country' => $storeLocation->country->iso ?? '',
                ],
                'delivery' => [
                    'city' => $order->shippingAddress->city ?? '',
                    'postcode' => $order->shippingAddress->zipCode ?? '',
                    'state' => $order->shippingAddress->state->abbreviation ?? '',
                    'country' => $order->shippingAddress->country->iso ?? '',
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
                        'amount' => (float)$service['price'] ?? '',
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
        } catch (\Throwable $e) {
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
                'collection' => [
                    'city' => $sender->city ?? '',
                    'postcode' => $sender->zipCode ?? '',
                    'state' => $sender->state->abbreviation ?? '',
                    'country' => $sender->country->iso ?? '',
                ],
                'delivery' => [
                    'city' => $recipient->city ?? '',
                    'postcode' => $recipient->zipCode ?? '',
                    'state' => $recipient->state->abbreviation ?? '',
                    'country' => $recipient->country->iso ?? '',
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

    private function _getClient(): \GuzzleHttp\Client
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
