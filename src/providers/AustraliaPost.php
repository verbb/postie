<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

class AustraliaPost extends Provider
{
    // Properties
    // =========================================================================

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
            // Domestic
            'AUS_PARCEL_COURIER'                => 'AusPost Domestic Courier Post',
            'AUS_PARCEL_COURIER_SATCHEL_MEDIUM' => 'AusPost Domestic Courier Post Assessed Medium Satchel',
            'AUS_PARCEL_EXPRESS'                => 'AusPost Domestic Express Post',
            'AUS_PARCEL_EXPRESS_SATCHEL_500G'   => 'AusPost Domestic Express Post Small Satchel',
            'AUS_PARCEL_REGULAR'                => 'AusPost Domestic Parcel Post',
            'AUS_PARCEL_REGULAR_SATCHEL_500G'   => 'AusPost Domestic Parcel Post Small Satchel',

            // International
            'INT_PARCEL_COR_OWN_PACKAGING' => 'AusPost International Courier',
            'INT_PARCEL_EXP_OWN_PACKAGING' => 'AusPost International Express',
            'INT_PARCEL_STD_OWN_PACKAGING' => 'AusPost International Standard',
            'INT_PARCEL_AIR_OWN_PACKAGING' => 'AusPost International Economy Air',
            'INT_PARCEL_SEA_OWN_PACKAGING' => 'AusPost International Economy Sea',
        ];
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

            if ($order->shippingAddress->country->iso == 'AU') {
                Provider::log($this, 'Domestic API call');

                $payload = [
                    'from_postcode' => $storeLocation->zipCode,
                    'to_postcode' => $order->shippingAddress->zipCode,
                    'length' => $dimensions['length'],
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'weight' => $dimensions['weight'],
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $response = $this->_request('GET', 'postage/parcel/domestic/service.json', [
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
                    'weight' => $dimensions['weight'],
                ];

                $this->beforeSendPayload($this, $payload, $order);

                $response = $this->_request('GET', 'postage/parcel/international/service.json', [
                    'query' => $payload,
                ]);
            }

            if (isset($response['services']['service'])) {
                foreach ($response['services']['service'] as $service) {
                    $this->_rates[$service['code']] = [
                        'amount' => (float)$service['price'] ?? '',
                        'options' => $service,
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


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://digitalapi.auspost.com.au',
                'headers' => [
                    'AUTH-KEY' => $this->settings['apiKey'],
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
