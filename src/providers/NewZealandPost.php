<?php
namespace verbb\postie\providers;

use verbb\postie\base\SinglePackageProvider;
use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class NewZealandPost extends SinglePackageProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'New Zealand Post');
    }

    public static function supportsDynamicServices(): bool
    {
        return true;
    }
    

    // Properties
    // =========================================================================

    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg'; // 25kg

    private int $maxDomesticWeight = 25000; // 30kg
    private int $maxInternationalWeight = 30000;


    // Public Methods
    // =========================================================================

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
        // $country = Commerce::getInstance()->countries->getCountryByIso('NZ');

        // $storeLocation = new craft\elements\Address();
        // $storeLocation->addressLine1 = '109 Wakefield Street';
        // $storeLocation->locality = 'Wellington';
        // $storeLocation->postalCode = '6011';
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->addressLine1 = '86 Kilmore Street';
        // $order->shippingAddress->locality = 'Christchurch';
        // $order->shippingAddress->postalCode = '8013';
        // $order->shippingAddress->countryId = $country->id;

        // // International
        // $country = Commerce::getInstance()->countries->getCountryByIso('AU');
        // $administrativeArea = Commerce::getInstance()->administrativeAreas->getadministrativeAreaByAbbreviation($country->id, 'TAS');

        // $order->shippingAddress->addressLine1 = '10-14 Cameron Street';
        // $order->shippingAddress->locality = 'Launceston';
        // $order->shippingAddress->postalCode = '7250';
        // $order->shippingAddress->administrativeAreaId = $administrativeArea->id;
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            $countryCode = $order->shippingAddress->countryCode ?? '';

            if ($countryCode == 'NZ') {
                Provider::info($this, 'Domestic API call');

                $payload = [
                    'pickup_city' => $storeLocation->locality ?? '',
                    'pickup_postcode' => $storeLocation->postalCode ?? '',
                    'pickup_country' => $storeLocation->countryCode ?? '',
                    'delivery_city' => $order->shippingAddress->locality ?? '',
                    'delivery_postcode' => $order->shippingAddress->postalCode ?? '',
                    'delivery_country' => $order->shippingAddress->countryCode ?? '',
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
                                'amount' => (float)($service['price_including_surcharge_and_gst'] ?? 0),
                                'options' => $service,
                            ],
                        ]);
                    }
                } else {
                    Provider::info($this, Craft::t('postie', 'No services found: `{json}`.', [
                        'json' => Json::encode($response),
                    ]));
                }
            } else {
                Provider::info($this, 'International API call');

                $payload = [
                    'country_code' => $order->shippingAddress->countryCode ?? '',
                    'value' => $packedBoxes->getTotalPrice(),
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
                                'amount' => (float)($service['price_including_gst'] ?? 0),
                                'options' => $service,
                            ],
                        ]);
                    }
                } else {
                    Provider::info($this, Craft::t('postie', 'No services found: `{json}`.', [
                        'json' => Json::encode($response),
                    ]));
                }
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
            $sender = TestingHelper::getTestAddress('NZ', ['locality' => 'Wellington']);
            $recipient = TestingHelper::getTestAddress('NZ', ['locality' => 'Christchurch']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'pickup_city' => $sender->locality ?? '',
                'pickup_postcode' => $sender->postalCode ?? '',
                'pickup_country' => $sender->countryCode ?? '',
                'delivery_city' => $recipient->locality ?? '',
                'delivery_postcode' => $recipient->postalCode ?? '',
                'delivery_country' => $recipient->countryCode ?? '',
                'envelope_size' => 'ALL',
                'weight' => $packedBox['weight'],
                'length' => $packedBox['length'],
                'width' => $packedBox['width'],
                'height' => $packedBox['height'],
            ];

            $response = $this->_request('GET', 'domestic', [
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
