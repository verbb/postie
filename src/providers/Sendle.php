<?php
namespace verbb\postie\providers;

use verbb\postie\base\SinglePackageProvider;
use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Sendle extends SinglePackageProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Sendle');
    }

    // Properties
    // =========================================================================

    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg'; // 25kg

    private int $maxDomesticWeight = 25000; // 70lbs
    private float $maxInternationalWeight = 31751.5;


    // Public Methods
    // =========================================================================

    public function supportsDynamicServices(): bool
    {
        return true;
    }

    public function getMaxPackageWeight($order): ?int
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
    }


    // Protected Methods
    // =========================================================================

    protected function fetchShippingRate($order, $storeLocation, $packedBox, $packedBoxes)
    {
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
                'pickup_suburb' => $storeLocation->city ?? '',
                'pickup_postcode' => $storeLocation->zipCode ?? '',
                'pickup_country' => $storeLocation->country->iso ?? '',
                'delivery_suburb' => $order->shippingAddress->city ?? '',
                'delivery_postcode' => $order->shippingAddress->zipCode ?? '',
                'delivery_country' => $order->shippingAddress->country->iso ?? '',
                'weight_value' => $packedBox['weight'],
                'weight_units' => $this->weightUnit,
            ];

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('GET', 'quote', [
                'query' => $payload,
            ]);

            if ($response) {
                foreach ($response as $service) {
                    // Update our overall rates, set the cache, etc
                    $this->setRate($packedBox, [
                        'key' => $service['plan_name'],
                        'value' => [
                            'amount' => (float)($service['quote']['gross']['amount'] ?? 0),
                            'options' => $service,
                        ],
                    ]);
                }
            } else {
                Provider::log($this, Craft::t('postie', 'No services found: `{json}`.', [
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

        return $response;
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
                'pickup_suburb' => $sender->city ?? '',
                'pickup_postcode' => $sender->zipCode ?? '',
                'pickup_country' => $sender->country->iso ?? '',
                'delivery_suburb' => $recipient->city ?? '',
                'delivery_postcode' => $recipient->zipCode ?? '',
                'delivery_country' => $recipient->country->iso ?? '',
                'weight_value' => $packedBox['weight'],
                'weight_units' => $this->weightUnit,
            ];

            $response = $this->_request('GET', 'quote', [
                'query' => $payload,
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

        $url = 'https://api.sendle.com/api/';

        if ($this->getSetting('useSandbox')) {
            $url = 'https://sandbox.sendle.com/api/';
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => $url,
            'auth' => [$this->getSetting('sendleId'), $this->getSetting('apiKey')],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

}
