<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use craft\commerce\Plugin as Commerce;

class DHLExpress extends Provider
{
    // Properties
    // =========================================================================

    public $name = 'DHL Express';


    // Public Methods
    // =========================================================================

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('postie/providers/dhl-express', ['provider' => $this]);
    }

    public function getServiceList(): array
    {
        // DHL don't really have set service codes, so have them be dynamic
        return [];
    }

    public function getShippingMethods($order)
    {
        // Get the dynamically generated service codes from the actual rate request
        $shippingMethods = [];

        $shippingRates = $this->getShippingRates($order) ?? [];

        foreach (array_keys($shippingRates) as $key => $handle) {
            $shippingMethod = new ShippingMethod();
            $shippingMethod->handle = $handle;
            $shippingMethod->provider = $this;
            $shippingMethod->name = StringHelper::toTitleCase($handle);
            $shippingMethod->enabled = true;

            $shippingMethods[$handle] = $shippingMethod;
        }

        return $shippingMethods;
    }

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();
        $dimensions = $this->getDimensions($order, 'kg', 'cm');

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $dimensions, $order);

        //
        // TESTING
        //
        // $country = Commerce::getInstance()->countries->getCountryByIso('DE');
        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'DE');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->city = 'Berlin';
        // $storeLocation->zipCode = '12345';
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->city = 'Berlin';
        // $order->shippingAddress->zipCode = '12345';
        // $order->shippingAddress->countryId = $country->id;

        // $country = Commerce::getInstance()->countries->getCountryByIso('US');
        // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'CA');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->address1 = 'One Infinite Loop';
        // $storeLocation->city = 'Cupertino';
        // $storeLocation->zipCode = '95014';
        // $storeLocation->stateId = $state->id;
        // $storeLocation->countryId = $country->id;

        // $order->shippingAddress->address1 = '1600 Amphitheatre Parkway';
        // $order->shippingAddress->city = 'Mountain View';
        // $order->shippingAddress->zipCode = '94043';
        // $order->shippingAddress->stateId = $state->id;
        // $order->shippingAddress->countryId = $country->id;
        //
        // 
        //

        try {
            $response = [];

            $payload = [
                'RateRequest' => [
                    'ClientDetails' => '',
                    'RequestedShipment' => [
                        'DropOffType' => 'REGULAR_PICKUP',
                        'ShipTimestamp' => (new \DateTime)->format('Y-m-d\TH:i:s \G\M\TP'),
                        'UnitOfMeasurement' => 'SI',
                        'Content' => 'NON_DOCUMENTS',
                        'DeclaredValue' => $order->totalPrice,
                        'DeclaredValueCurrecyCode' => $order->currency,
                        'PaymentInfo' => 'DAP',
                        'Account' => $this->settings['account'],

                        'Ship' => [
                            'Shipper' => [
                                'City' => $storeLocation->city ?? '',
                                'PostalCode' => $storeLocation->zipCode ?? '',
                                'CountryCode' => $storeLocation->country->iso ?? '',
                            ],
                            'Recipient' => [
                                'City' => $order->shippingAddress->city ?? '',
                                'PostalCode' => $order->shippingAddress->zipCode ?? '',
                                'CountryCode' => $order->shippingAddress->country->iso ?? '',
                            ],
                        ],
                        'Packages' => [
                            'RequestedPackages' => [
                                '@number' => 1,
                                'Weight' => [
                                    'Value' => $dimensions['weight'],
                                ],
                                'Dimensions' => [
                                    'Length' => $dimensions['length'],
                                    'Width' => $dimensions['width'],
                                    'Height' => $dimensions['height'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->_request('POST', 'RateRequest', [
                'json' => $payload,
            ]);

            $services = $response['RateResponse']['Provider'][0]['Service'] ?? [];

            // Check this is a correct array
            if (!isset($services[0])) {
                $services = [$services];
            }

            foreach ($services as $service) {
                $name = $service['Charges']['Charge'][0]['ChargeType'] ?? 'GENERAL';
                $amount = (float)($service['TotalNet']['Amount'] ?? 0);

                if ($amount) {
                    $this->_rates[$name] = [
                        'amount' => $amount,
                        'options' => $service,
                    ];
                }
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

            if (!$this->_rates) {
                Provider::error($this, 'No available rates: `' . json_encode($response) . '`.');
            }
        } catch (\Throwable $e) {
            if (method_exists($e, 'hasResponse')) {
                $data = Json::decode((string)$e->getResponse()->getBody());

                if (isset($data['error']['errorMessage'])) {
                    Provider::error($this, 'API error: `' . $data['error']['errorMessage'] . '`.');
                } else {
                    Provider::error($this, 'API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
                }
            } else {
                Provider::error($this, 'API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
            }
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            $url = 'https://wsbexpress.dhl.com/rest/gbl/';

            if ($this->settings['useTestEndpoint']) {
                $url = 'https://wsbexpress.dhl.com/rest/sndpt/';
            }

            $this->_client = Craft::createGuzzleClient([
                'base_uri' => $url,
                'auth' => [
                    $this->settings['username'], $this->settings['password'],
                ],
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        return Json::decode((string)$response->getBody());
    }
}
