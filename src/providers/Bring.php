<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;

use function GuzzleHttp\Psr7\build_query;

class Bring extends Provider
{
    // Properties
    // =========================================================================

    public string $weightUnit = 'g';
    public string $dimensionUnit = 'cm';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Bring');
    }


    // Public Methods
    // =========================================================================

    public function getServiceList(): array
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
        // $country = Commerce::getInstance()->countries->getCountryByIso('NO');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->zipCode = '0470';
        // $storeLocation->countryId = $country->id;

        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'NO');
        // $order->shippingAddress->zipCode = '0151';
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            $payload = [
                'frompostalcode' => $storeLocation->zipCode ?? '',
                'fromcountry' => $storeLocation->country->iso ?? '',
                'topostalcode' => $order->shippingAddress->zipCode ?? '',
                'tocountry' => $order->shippingAddress->country->iso ?? '',
                'postingatpostoffice' => 'false',
                'weight' => $packedBoxes->getTotalWeight(),

                // Tells whether the parcel is delivered at a post office when it is shipped.
                // A surcharge will be applied for SERVICEPAKKE and BPAKKE_DOR-DOR
                'postingatpostoffice' => 'false',
            ];

            // Restrict the services we fetch, is enabled
            if ($this->restrictServices) {
                $payload['product'] = array_keys(ArrayHelper::where($this->services, 'enabled', true));
            } else {
                $payload['product'] = array_keys($this->getServiceList());
            }

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('GET', 'products', [
                'query' => build_query($payload),
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
            $sender = TestingHelper::getTestAddress('NO', ['city' => 'Oslo']);
            $recipient = TestingHelper::getTestAddress('NO', ['city' => 'Bergen']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $payload = [
                'frompostalcode' => $sender->zipCode ?? '',
                'fromcountry' => $sender->country->iso ?? '',
                'topostalcode' => $recipient->zipCode ?? '',
                'tocountry' => $recipient->country->iso ?? '',
                'postingatpostoffice' => 'false',
                'weight' => $packedBox['weight'],
                'postingatpostoffice' => 'false',
                'product' => ['PA_DOREN'],
            ];

            $response = $this->_request('GET', 'products', [
                'query' => build_query($payload),
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
