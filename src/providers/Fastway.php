<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

class Fastway extends Provider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'kg';
    public $dimensionUnit = 'cm';

    private $maxWeight = 5000; // 5kg


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Fastway');
    }

    public function getServiceList(): array
    {
        return [
            'RED' => 'Road Parcel (Red)',
            'GREEN' => 'Road Parcel (Green)',

            'BROWN' => 'Local Parcel (Brown)',
            'BLACK' => 'Local Parcel (Black)',
            'BLUE' => 'Local Parcel (Blue)',
            'YELLOW' => 'Local Parcel (Yellow)',

            'PINK' => 'Shorthaul Parcel (Pink)',

            'SAT_NAT_A2' => 'National Network A2 Satchel',
            'SAT_NAT_A3' => 'National Network A3 Satchel',
            'SAT_NAT_A4' => 'National Network A4 Satchel',
            'SAT_NAT_A5' => 'National Network A5 Satchel',
        ];
    }

    public function getMaxPackageWeight($order)
    {
        return $this->maxWeight;
    }

    public function fetchShippingRates($order)
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
            $countryCode = $this->_getCountryCode($order->shippingAddress->country);

            if (!$countryCode) {
                return false;
            }

            $response = $this->_request('GET', 'pickuprf/' . $storeLocation->zipCode . '/' . $countryCode);
            $franchiseCode = $response['result']['franchise_code'] ?? false;

            if (!$franchiseCode) {
                return false;
            }

            $url = [
                'lookup',
                $franchiseCode,
                $order->shippingAddress->city,
                $order->shippingAddress->zipCode,
                $packedBoxes->getTotalWeight(),
            ];

            $response = $this->_request('GET', implode('/', $url));

            if (isset($response['result']['services'])) {
                foreach ($response['result']['services'] as $service) {
                    $serviceHandle = $this->_getServiceHandle($service['labelcolour']);

                    $this->_rates[$serviceHandle] = [
                        'amount' => (float)$service['totalprice_normal'] ?? '',
                        'options' => $response['result'],
                    ];
                }
            } else {
                Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
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
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
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
            $countryCode = $this->_getCountryCode('Australia');
            $response = $this->_request('GET', 'pickuprf/' . $sender->zipCode . '/' . $countryCode);
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
                'base_uri' => 'https://au.api.fastway.org/v4/psc/',
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $options = array_merge($options, ['query' => ['api_key' => $this->getSetting('apiKey')]]);

        $response = $this->_getClient()->request($method, $uri, $options);

        return Json::decode((string)$response->getBody());
    }

    private function _getServiceHandle($string)
    {
        $string = str_replace('-', '_', $string);

        return $string;
    }

    private function _getCountryCode($country)
    {
        if ($country == 'Australia') {
            return 1;
        } else if ($country == 'New Zealand') {
            return 6;
        } else if ($country == 'Ireland') {
            return 11;
        } else if ($country == 'South Africa') {
            return 24;
        }

        return false;
    }
}
