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

class AustraliaPost extends SinglePackageProvider
{
    // Constants
    // =========================================================================

    const TYPE_BOX = 'box';
    const TYPE_ENVELOPE = 'envelope';
    const TYPE_PACKET = 'packet';
    const TYPE_TUBE = 'tube';


    // Properties
    // =========================================================================

    public $weightUnit = 'kg';
    public $dimensionUnit = 'cm';

    private $maxDomesticWeight = 22000; // 22kg
    private $maxInternationalWeight = 20000; // 20kg
    private $_countryList = [];


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Australia Post');
    }

    public function getServiceList(): array
    {
        return [
            // Domestic - Parcel
            'AUS_PARCEL_REGULAR' => 'Australia Post Parcel Post',
            'AUS_PARCEL_REGULAR_SATCHEL_500G' => 'Australia Post Parcel Post Small Satchel',
            'AUS_PARCEL_REGULAR_SATCHEL_3KG' => 'Australia Post Parcel Post Small Satchel',
            'AUS_PARCEL_REGULAR_SATCHEL_5KG' => 'Australia Post Parcel Post Small Satchel',
            'AUS_PARCEL_EXPRESS' => 'Australia Post Express Post',
            'AUS_PARCEL_EXPRESS_SATCHEL_500G' => 'Australia Post Express Post Small Satchel',
            'AUS_PARCEL_EXPRESS_SATCHEL_3KG' => 'Australia Post Express Post Medium (3Kg) Satchel',
            'AUS_PARCEL_EXPRESS_SATCHEL_5KG' => 'Australia Post Express Post Large (5Kg) Satchel',
            'AUS_PARCEL_COURIER' => 'Australia Post Courier Post',
            'AUS_PARCEL_COURIER_SATCHEL_MEDIUM' => 'Australia Post Courier Post Assessed Medium Satchel',
            
            // Domestic - Letter
            'AUS_LETTER_REGULAR_SMALL' => 'Australia Post Letter Regular Small',
            'AUS_LETTER_REGULAR_MEDIUM' => 'Australia Post Letter Regular Medium',
            'AUS_LETTER_REGULAR_LARGE' => 'Australia Post Letter Regular Large',
            'AUS_LETTER_REGULAR_LARGE_125' => 'Australia Post Letter Regular Large (125g)',
            'AUS_LETTER_REGULAR_LARGE_250' => 'Australia Post Letter Regular Large (250g)',
            'AUS_LETTER_REGULAR_LARGE_500' => 'Australia Post Letter Regular Large (500g)',
            'AUS_LETTER_EXPRESS_SMALL' => 'Australia Post Letter Express Small',
            'AUS_LETTER_EXPRESS_MEDIUM' => 'Australia Post Letter Express Medium',
            'AUS_LETTER_EXPRESS_LARGE' => 'Australia Post Letter Express Large',
            'AUS_LETTER_EXPRESS_LARGE_125' => 'Australia Post Letter Express Large (125g)',
            'AUS_LETTER_EXPRESS_LARGE_250' => 'Australia Post Letter Express Large (250g)',
            'AUS_LETTER_EXPRESS_LARGE_500' => 'Australia Post Letter Express Large (500g)',
            'AUS_LETTER_PRIORITY_SMALL' => 'Australia Post Letter Priority Small',
            'AUS_LETTER_PRIORITY_MEDIUM' => 'Australia Post Letter Priority Medium',
            'AUS_LETTER_PRIORITY_LARGE' => 'Australia Post Letter Priority Large',
            'AUS_LETTER_PRIORITY_LARGE_125' => 'Australia Post Letter Priority Large (125g)',
            'AUS_LETTER_PRIORITY_LARGE_250' => 'Australia Post Letter Priority Large (250g)',
            'AUS_LETTER_PRIORITY_LARGE_500' => 'Australia Post Letter Priority Large (500g)',

            // International - Parcel
            'INT_PARCEL_STD_OWN_PACKAGING' => 'Australia Post International Standard',
            'INT_PARCEL_EXP_OWN_PACKAGING' => 'Australia Post International Express',
            'INT_PARCEL_COR_OWN_PACKAGING' => 'Australia Post International Courier',
            'INT_PARCEL_AIR_OWN_PACKAGING' => 'Australia Post International Economy Air',
            'INT_PARCEL_SEA_OWN_PACKAGING' => 'Australia Post International Economy Sea',

            // International - Letter
            'INT_LETTER_REG_SMALL_ENVELOPE' => 'Australia Post International Letter DL',
            'INT_LETTER_REG_LARGE_ENVELOPE' => 'Australia Post International Letter B4',
            'INT_LETTER_EXP_OWN_PACKAGING' => 'Australia Post International Letter Express',
            'INT_LETTER_COR_OWN_PACKAGING' => 'Australia Post International Letter Courier',
            'INT_LETTER_AIR_OWN_PACKAGING_LIGHT' => 'Australia Post International Letter Air Light',
            'INT_LETTER_AIR_OWN_PACKAGING_MEDIUM' => 'Australia Post International Letter Air Medium',
            'INT_LETTER_AIR_OWN_PACKAGING_HEAVY' => 'Australia Post International Letter Air Heavy',
        ];
    }

    public static function defineDefaultBoxes()
    {
        return [
            [
                'id' => 'auspost-letter-1',
                'name' => 'DL 110 x 220',
                'boxLength' => 11,
                'boxWidth' => 22,
                'boxHeight' => 0.5,
                'boxWeight' => 0,
                'maxWeight' => 0.25,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-2',
                'name' => 'DL 130 x 240',
                'boxLength' => 13,
                'boxWidth' => 24,
                'boxHeight' => 0.5,
                'boxWeight' => 0,
                'maxWeight' => 0.25,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-3',
                'name' => 'C5 162 x 229',
                'boxLength' => 16.2,
                'boxWidth' => 22.9,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-4',
                'name' => 'C4 324 x 229',
                'boxLength' => 32.4,
                'boxWidth' => 22.9,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-letter-5',
                'name' => 'B4 353 x 250',
                'boxLength' => 35.3,
                'boxWidth' => 25,
                'boxHeight' => 2,
                'boxWeight' => 0,
                'maxWeight' => 0.5,
                'boxType' => self::TYPE_ENVELOPE,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-1',
                'name' => 'Small Satchel',
                'boxLength' => 35.5,
                'boxWidth' => 22.5,
                'boxHeight' => 8,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-2',
                'name' => 'Medium Satchel',
                'boxLength' => 39,
                'boxWidth' => 27,
                'boxHeight' => 12,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-3',
                'name' => 'Large Satchel',
                'boxLength' => 41.5,
                'boxWidth' => 31.5,
                'boxHeight' => 14,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-satchel-4',
                'name' => 'Extra Large Satchel',
                'boxLength' => 44,
                'boxWidth' => 51,
                'boxHeight' => 15,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_PACKET,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-1',
                'name' => 'Small Box',
                'boxLength' => 16,
                'boxWidth' => 22,
                'boxHeight' => 7,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-2',
                'name' => 'Medium Box',
                'boxLength' => 19,
                'boxWidth' => 24,
                'boxHeight' => 12,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-3',
                'name' => 'Large Box',
                'boxLength' => 28,
                'boxWidth' => 39,
                'boxHeight' => 14,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
            [
                'id' => 'auspost-box-4',
                'name' => 'Extra Large Box',
                'boxLength' => 27.7,
                'boxWidth' => 44,
                'boxHeight' => 16.8,
                'boxWeight' => 0,
                'maxWeight' => 5,
                'boxType' => self::TYPE_BOX,
                'enabled' => true,
            ],
        ];
    }

    public function getBoxSizesSettings()
    {
        $sizes = parent::getBoxSizesSettings();

        $newCols = [
            'boxType' => [
                'type' => 'select',
                'heading' => Craft::t('postie', 'Type'),
                'thin' => true,
                'small' => true,
                'options' => [
                    ['label' => Craft::t('postie', 'Box'), 'value' => self::TYPE_BOX],
                    ['label' => Craft::t('postie', 'Envelope'), 'value' => self::TYPE_ENVELOPE],
                    ['label' => Craft::t('postie', 'Packet'), 'value' => self::TYPE_PACKET],
                    ['label' => Craft::t('postie', 'Tube'), 'value' => self::TYPE_TUBE],
                ],
            ],
        ];

        // Add the new column, but before the enabled lightswitch
        $index = array_search('enabled', array_keys($sizes));
        $sizes = array_merge(array_slice($sizes, 0, $index), $newCols, array_slice($sizes, $index));

        return $sizes;
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

    protected function fetchShippingRate($order, $storeLocation, $packedBox)
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

            $type = $packedBox['type'] ?? '';
            $countryIso = $order->shippingAddress->country->iso ?? '';

            if ($countryIso === 'AU') {
                Provider::log($this, 'Domestic API call');

                $payload = [
                    'from_postcode' => $storeLocation->zipCode,
                    'to_postcode' => $order->shippingAddress->zipCode,
                    'length' => $packedBox['length'],
                    'width' => $packedBox['width'],
                    'height' => $packedBox['height'],
                    'weight' => $packedBox['weight'],
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $endpoint = 'postage/parcel/domestic/service.json';

                // Check if we should fetch letter pricing - depending on if this packed box has fitted into an envelope
                if ($type === self::TYPE_ENVELOPE) {
                    $endpoint = 'postage/letter/domestic/service.json';
                }

                $response = $this->_request('GET', $endpoint, [
                    'query' => $payload,
                ]);
            } else {
                Provider::log($this, 'International API call');

                // Get match country code from Aus Pos country list
                $countryCode = $this->_getCountryCode($order->shippingAddress->country);

                if (!$countryCode) {
                    return false;
                }

                $payload = [
                    'country_code' => $countryCode,
                    'weight' => $packedBox['weight'],
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $endpoint = 'postage/parcel/international/service.json';

                // Check if we should fetch letter pricing - depending on if this packed box has fitted into an envelope
                if ($type === self::TYPE_ENVELOPE) {
                    $endpoint = 'postage/letter/international/service.json';
                }

                $response = $this->_request('GET', $endpoint, [
                    'query' => $payload,
                ]);
            }

            $services = $response['services']['service'] ?? [];

            // The AusPost API doesn't normalise services returned. If only on serviec is returned, it won't be a multi-dimensional
            // array, which really throws things off. So ensure we normalise services.
            if (!isset($services[0])) {
                $services = [$services];
            }

            if ($services) {
                foreach ($services as $service) {
                    // Update our overall rates, set the cache, etc
                    $this->setRate($packedBox, [
                        'key' => $service['code'],
                        'value' => [
                            'amount' => (float)$service['price'] ?? '',
                            'options' => $service,
                        ],
                    ]);
                }
            } else {
                Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
                    'json' => Json::encode($response),
                ]));
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
            $sender = TestingHelper::getTestAddress('AU', ['state' => 'VIC']);
            $recipient = TestingHelper::getTestAddress('AU', ['state' => 'TAS']);

            // Create a test package - API only accepts cm/kg
            $packedBoxes = TestingHelper::getTestPackedBoxes('cm', 'kg');
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'from_postcode' => $sender->zipCode,
                'to_postcode' => $recipient->zipCode,
                'length' => $packedBox['length'],
                'width' => $packedBox['width'],
                'height' => $packedBox['height'],
                'weight' => $packedBox['weight'],
            ];

            $response = $this->_request('GET', 'postage/parcel/domestic/service.json', [
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
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://digitalapi.auspost.com.au',
                'headers' => [
                    'AUTH-KEY' => $this->getSetting('apiKey'),
                ]
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        return Json::decode((string)$response->getBody());
    }

    private function _getCountryCode($country)
    {
        // Try to fetch live country codes, otherwise, fall back to our local cache
        try {
            if (!$this->_countryList) {
                $this->_countryList = $this->_request('GET', 'postage/country.json');
            }
        } catch (\Throwable $e) {
            $cachePath = Craft::getAlias('@vendor/verbb/postie/src/inc/australia-post/countries.json');

            $this->_countryList = Json::decode(file_get_contents($cachePath));
        }
            
        foreach ($this->_countryList['countries']['country'] as $countryListItem) {
            if (strtoupper($country) == $countryListItem['name']) {
                return $countryListItem['code'];
            }
        }

        return false;
    }
}
