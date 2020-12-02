<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\SinglePackageProvider;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

class NewZealandPost extends SinglePackageProvider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'kg';
    public $dimensionUnit = 'cm';

    private $maxDomesticWeight = 25000; // 25kg
    private $maxInternationalWeight = 30000; // 30kg

    
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'New Zealand Post');
    }

    public function supportsDynamicServices(): bool
    {
        return true;
    }

    public function getMaxPackageWeight($order)
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
    }


    // Protected Methods
    // =========================================================================

    protected function fetchShippingRate($order, $packedBox)
    {
        //
        // TESTING
        //
        // $country = Commerce::getInstance()->countries->getCountryByIso('NZ');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->address1 = '109 Wakefield Street';
        // $storeLocation->city = 'Wellington';
        // $storeLocation->zipCode = '6011';
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->address1 = '86 Kilmore Street';
        // $order->shippingAddress->city = 'Christchurch';
        // $order->shippingAddress->zipCode = '8013';
        // $order->shippingAddress->countryId = $country->id;

        // // International
        // $country = Commerce::getInstance()->countries->getCountryByIso('AU');
        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'TAS');

        // $order->shippingAddress->address1 = '10-14 Cameron Street';
        // $order->shippingAddress->city = 'Launceston';
        // $order->shippingAddress->zipCode = '7250';
        // $order->shippingAddress->stateId = $state->id;
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            if ($order->shippingAddress->country->iso == 'NZ') {
                Provider::log($this, 'Domestic API call');

                $payload = [
                    'pickup_city' => $storeLocation->city,
                    'pickup_postcode' => $storeLocation->zipCode,
                    'pickup_country' => $storeLocation->country->iso,
                    'delivery_city' => $order->shippingAddress->city,
                    'delivery_postcode' => $order->shippingAddress->zipCode,
                    'delivery_country' => $order->shippingAddress->country->iso,
                    'envelope_size' => 'ALL',
                    'weight' => $packedBox['weight'],
                    'length' => $packedBox['length'],
                    'width' => $packedBox['width'],
                    'height' => $packedBox['height'],
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $response = $this->_request('GET', 'domestic', [
                    'query' => $payload,
                ]);

                $services = $response['services'] ?? [];

                if ($services) {
                    foreach ($services as $service) {
                        // Update our overall rates, set the cache, etc
                        $this->setRate($packedBox, [
                            'key' => $service['description'],
                            'value' => [
                                'amount' => (float)$service['price_including_surcharge_and_gst'] ?? '',
                                'options' => $service,
                            ],
                        ]);
                    }
                } else {
                    Provider::log($this, Craft::t('postie', 'No services found: `{json}`.', [
                        'json' => Json::encode($response),
                    ]));
                }
            } else {
                Provider::log($this, 'International API call');

                $payload = [
                    'country_code' => $order->shippingAddress->country->iso,
                    'value' => (float)$order->total,
                    'weight' => $packedBox['weight'],
                    'length' => $packedBox['length'],
                    'width' => $packedBox['width'],
                    'height' => $packedBox['height'],
                    'format' => 'json',
                    'documents' => '',
                    'account_number' => $this->getSetting('accountNumber'),
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $response = $this->_request('GET', 'international', [
                    'query' => $payload,
                ]);

                $services = $response['services'] ?? [];

                if ($services) {
                    foreach ($services as $service) {
                        // Update our overall rates, set the cache, etc
                        $this->setRate($packedBox, [
                            'key' => $service['description'],
                            'value' => [
                                'amount' => (float)$service['price_including_gst'] ?? '',
                                'options' => $service,
                            ],
                        ]);
                    }
                } else {
                    Provider::log($this, Craft::t('postie', 'No services found: `{json}`.', [
                        'json' => Json::encode($response),
                    ]));
                }
            }
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

        return $response;
    }

    protected function fetchConnection(): bool
    {
        try {
            // Create test addresses
            $sender = TestingHelper::getTestAddress('NZ', ['city' => 'Wellington']);
            $recipient = TestingHelper::getTestAddress('NZ', ['city' => 'Christchurch']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'pickup_city' => $sender->city,
                'pickup_postcode' => $sender->zipCode,
                'pickup_country' => $sender->country->iso,
                'delivery_city' => $recipient->city,
                'delivery_postcode' => $recipient->zipCode,
                'delivery_country' => $recipient->country->iso,
                'envelope_size' => 'ALL',
                'weight' => $packedBox['weight'],
                'length' => $packedBox['length'],
                'width' => $packedBox['width'],
                'height' => $packedBox['height'],
            ];

            $response = $this->_request('GET', 'domestic', [
                'query' => $payload,
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

    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $url = 'https://api.nzpost.co.nz/shippingoptions/2.0/';

        if ($this->getSetting('useTestEndpoint')) {
            $url = 'https://api.uat.nzpost.co.nz/shippingoptions/2.0/';
        }

        // Fetch an access token first
        $authResponse = Json::decode((string)Craft::createGuzzleClient()
            ->request('POST', 'https://oauth.nzpost.co.nz/as/token.oauth2', [
                'query' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->getSetting('clientId'),
                    'client_secret' => $this->getSetting('clientSecret'),
                ],
            ])->getBody());

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => $url,
            'headers' => [
                'client_id' => $this->getSetting('clientId'),
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

}
