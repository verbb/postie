<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;

use GuzzleHttp\Client;

use Throwable;

class Bring extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Bring');
    }

    public static function getServiceList(): array
    {
        return [
            // https://developer.bring.com/files/Labelspecifications_for_Bring_v_4_991.pdf
            // Bring Parcels AB
            '0330' => 'Business Parcel',
            '0331' => 'Business Parcel Return',
            '0332' => 'Business Parcel Bulk',
            '0333' => 'Business Parcel Return Bulk',
            '0334' => 'Express Nordic 0900 Bulk',
            '0335' => 'Express Nordic 0900',
            '0336' => 'Business Pallet',
            '0339' => 'Express Nordic 0900 Pallet',
            '0340' => 'PickUp Parcel',
            '0341' => 'PickUp Parcel Return',
            '0342' => 'PickUp Parcel Bulk',
            '0343' => 'PickUp Parcel Return Bulk',
            '0345' => 'Home Delivery Mailbox',
            '0348' => 'Home Delivery Parcel Return',
            '0349' => 'Home Delivery Parcel',
            
            // Parcel Domestic
            '1000' => 'Bedriftspakke',
            '1002' => 'Bedriftspakke Ekspress-Over natten',
            '1020' => 'Postens pallelaster',
            '1202' => 'Klimanøytral Servicepakke',
            '1206' => 'Bedriftspakke postkontor',
            '1312' => 'På Døren Prosjekt',
            '1736' => 'På Døren',
            '1885' => 'Abonnementstransport',
            '1988' => 'Bedriftspakke Flerkolli',
            '3110' => 'Minipakke',

            '3570' => 'Pakke i postkassen',
            '3584' => 'Pakke i postkassen (sporbar)',
            '4850' => 'Ekspress neste dag',
            '5000' => 'Pakke til bedrift',
            '5100' => 'Stykkgods til bedrift',
            '5300' => 'Partigods til bedrift',
            '5400' => 'Pall til bedrift',
            '5600' => 'Pakke levert hjem',
            '5800' => 'Pakke til hentested',
            '9000' => 'Retur pakke fra bedrift',
            '9100' => 'Retur stykkgods fra bedrift',
            '9300' => 'Retur fra hentested',
            '9600' => 'Returekspress',
            'MAIL' => 'Brev',
            'VIP25' => 'Budbil VIP',
        ];
    }


    // Properties
    // =========================================================================

    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'g';


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
        // $storeLocation = TestingHelper::getTestAddress('NO', ['locality' => 'Oslo']);
        // $order->shippingAddress = TestingHelper::getTestAddress('NO', ['locality' => 'Bergen'], $order);
        //
        // 
        //

        try {
            $response = [];
            $packages = [];

            foreach ($packedBoxes->getSerializedPackedBoxList() as $index => $packedBox) {
                $packages[] = [
                    'grossWeight' => $packedBox['weight'],
                    'height' => $packedBox['height'],
                    'width' => $packedBox['width'],
                    'length' => $packedBox['length'],
                    'id' => $index + 1,
                ];
            }

            $payload = [
                'consignments' => [
                    [
                        'id' => '1',
                        'fromCountryCode' => $storeLocation->countryCode ?? '',
                        'fromPostalCode' => $storeLocation->postalCode ?? '',
                        'toCountryCode' => $order->shippingAddress->countryCode ?? '',
                        'toPostalCode' => $order->shippingAddress->postalCode ?? '',
                        'packages' => $packages,
                    ],
                ],

                // Tells whether the parcel is delivered at a post office when it is shipped.
                // A surcharge will be applied for SERVICEPAKKE and BPAKKE_DOR-DOR
                'postingAtPostoffice' => false,
            ];

            // Restrict the services we fetch, is enabled
            if ($this->restrictServices) {
                $payload['consignments'][0]['products'] = array_map(function($item) {
                    return ['id' => (string)$item];
                }, array_keys(ArrayHelper::where($this->services, 'enabled', true)));
            } else {
                $payload['consignments'][0]['products'] = array_map(function($item) {
                    return ['id' => (string)$item];
                }, array_keys(self::getServiceList()));
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'products', [
                'json' => $payload,
            ]);

            $services = $response['consignments'][0]['products'] ?? [];

            if ($services) {
                foreach ($services as $service) {
                    $price = $service['price']['listPrice']['priceWithoutAdditionalServices']['amountWithVAT'] ?? '';
                    $errors = $service['errors'] ?? [];

                    if ($errors) {
                        Provider::error($this, Craft::t('postie', 'Error fetching rate: `{json}`.', [
                            'json' => Json::encode($service),
                        ]));
                    } else {
                        $this->_rates[$service['id']] = [
                            'amount' => (float)$price,
                            'options' => $service,
                        ];
                    }
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
            $sender = TestingHelper::getTestAddress('NO', ['locality' => 'Oslo']);
            $recipient = TestingHelper::getTestAddress('NO', ['locality' => 'Bergen']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'consignments' => [
                    [
                        'id' => '1',
                        'fromCountryCode' => $sender->countryCode ?? '',
                        'fromPostalCode' => $sender->postalCode ?? '',
                        'toCountryCode' => $recipient->countryCode ?? '',
                        'toPostalCode' => $recipient->postalCode ?? '',
                        'packages' => [
                            [
                                'grossWeight' => $packedBox['weight'],
                                'id' => '1',
                            ],
                        ],
                        'products' => [
                            ['id' => 'PA_DOREN'],
                        ]
                    ],
                ],

                // Tells whether the parcel is delivered at a post office when it is shipped.
                // A surcharge will be applied for SERVICEPAKKE and BPAKKE_DOR-DOR
                'postingAtPostoffice' => false,
            ];

            $response = $this->_request('POST', 'products', [
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.bring.com/shippingguide/v2/',
            'headers' => [
                'X-MyBring-API-Uid' => $this->getSetting('username'),
                'X-MyBring-API-Key' => $this->getSetting('apiKey'),
                'X-Bring-Client-URL' => UrlHelper::siteUrl('/'),
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}
