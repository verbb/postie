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
            'SERVICEPAKKE' => 'Klimanøytral Servicepakke',
            'PA_DOREN' => 'På Døren',
            'BPAKKE_DOR-DOR' => 'Bedriftspakke',
            'EKSPRESS09' => 'Bedriftspakke Ekspress-Over natten',
            'MINIPAKKE' => 'Minipakke',
            'MAIL' => 'Brev',
            'A-POST' => 'A-Prioritert',
            'B-POST' => 'B-Økonomi',
            'SMAAPAKKER_A-POST' => 'Småpakker A-Post',
            'SMAAPAKKER_B-POST' => 'Småpakker B-Post',
            'QUICKPACK_SAMEDAY' => 'QuickPack SameDay',
            'QUICKPACK_OVER_NIGHT_0900' => 'Quickpack Over Night 0900',
            'QUICKPACK_OVER_NIGHT_1200' => 'Quickpack Over Night 1200',
            'QUICKPACK_DAY_CERTAIN' => 'Quickpack Day Certain',
            'QUICKPACK_EXPRESS_ECONOMY' => 'Quickpack Express Economy',
            'CARGO_GROUPAGE' => 'Cargo',
            'CARRYON_BUSINESS' => 'CarryOn Business',
            'CARRYON_HOMESHOPPING' => 'CarryOn HomeShopping',
            'HOMEDELIVERY_CURBSIDE_DAG' => 'HomeDelivery Curb Side',
            'COURIER_VIP' => 'Bud VIP',
            'COURIER_1H' => 'Bud 1 time',
            'COURIER_2H' => 'Bud 2 timer',
            'COURIER_4H' => 'Bud 4 timer',
            'COURIER_6H' => 'Bud 6 timer',
            'OX' => 'Oil Express',
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
        // $country = Commerce::getInstance()->countries->getCountryByIso('NO');

        // $storeLocation = new craft\elements\Address();
        // $storeLocation->postalCode = '0470';
        // $storeLocation->countryId = $country->id;

        // $administrativeArea = Commerce::getInstance()->administrativeAreas->getadministrativeAreaByAbbreviation($country->id, 'NO');
        // $order->shippingAddress->postalCode = '0151';
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            $payload = [
                'frompostalcode' => $storeLocation->postalCode ?? '',
                'fromcountry' => $storeLocation->countryCode ?? '',
                'topostalcode' => $order->shippingAddress->postalCode ?? '',
                'tocountry' => $order->shippingAddress->countryCode ?? '',
                'weight' => $packedBoxes->getTotalWeight(),

                // Tells whether the parcel is delivered at a post office when it is shipped.
                // A surcharge will be applied for SERVICEPAKKE and BPAKKE_DOR-DOR
                'postingatpostoffice' => 'false',
            ];

            // Restrict the services we fetch, is enabled
            if ($this->restrictServices) {
                $payload['product'] = array_keys(ArrayHelper::where($this->services, 'enabled', true));
            } else {
                $payload['product'] = array_keys(self::getServiceList());
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('GET', 'products', [
                'query' => $payload,
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
                Provider::info($this, Craft::t('postie', 'No services found: `{json}`.', [
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
                'frompostalcode' => $sender->postalCode ?? '',
                'fromcountry' => $sender->countryCode ?? '',
                'topostalcode' => $recipient->postalCode ?? '',
                'tocountry' => $recipient->countryCode ?? '',
                'postingatpostoffice' => 'false',
                'weight' => $packedBox['weight'],
                'product' => ['PA_DOREN'],
            ];

            $response = $this->_request('GET', 'products', [
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
