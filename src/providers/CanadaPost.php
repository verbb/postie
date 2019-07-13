<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Cake\Utility\Xml as XmlParser;

class CanadaPost extends Provider
{
    // Properties
    // =========================================================================

    public $name = 'Canada Post';


    // Public Methods
    // =========================================================================

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('postie/providers/canada-post', ['provider' => $this]);
    }

    public function getServiceList(): array
    {
        return [
            'DOM_EP' => 'Expedited Parcel',
            'DOM_RP' => 'Regular Parcel',
            'DOM_PC' => 'Priority',
            'DOM_XP' => 'Xpresspost',
            'INT_PW_ENV' => 'Priority Worldwide envelope INTL',
            'USA_PW_ENV' => 'Priority Worldwide envelope USA',
            'USA_PW_PAK' => 'Priority Worldwide pak USA',
            'INT_PW_PAK' => 'Priority Worldwide pak INTL',
            'INT_PW_PARCEL' => 'Priority Worldwide parcel INTL',
            'USA_PW_PARCEL' => 'Priority Worldwide parcel USA',
            'INT_XP' => 'Xpresspost International',
            'INT_IP_AIR' => 'International Parcel Air',
            'INT_IP_SURF' => 'International Parcel Surface',
            'INT_TP' => 'Tracked Packet - International',
            'INT_SP_SURF' => 'Small Packet International Surface',
            'INT_SP_AIR' => 'Small Packet International Air',
            'USA_XP' => 'Xpresspost USA',
            'USA_EP' => 'Expedited Parcel USA',
            'USA_TP' => 'Tracked Packet - USA',
            'USA_SP_AIR' => 'Small Packet USA Air',
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

        //
        // TESTING
        //
        // $storeLocation->zipCode = 'K1H 7S5'; 
        // $order->shippingAddress->zipCode = 'K1H 7S5';
        // $dimensions['weight'] = 0.45359237;
        //
        //
        //

        // Remove spaces in zip code
        $originZipCode = str_replace(' ', '', $storeLocation->zipCode); 
        $orderZipCode = str_replace(' ', '', $order->shippingAddress->zipCode);

        // API is very particular on format - float up to 3 decimal places
        $dimensions['weight'] = number_format($dimensions['weight'], 3);

        try {
            $xmlRequest = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v3">
    <customer-number>{$this->settings['customerNumber']}</customer-number>
    <parcel-characteristics>
        <weight>{$dimensions['weight']}</weight>
    </parcel-characteristics>
    <origin-postal-code>{$originZipCode}</origin-postal-code>
    <destination>
        <domestic>
            <postal-code>{$orderZipCode}</postal-code>
        </domestic>
    </destination>
</mailing-scenario>
XML;

            $response = $this->_request('POST', 'rs/ship/price', ['body' => $xmlRequest]);

            if (isset($response['price-quotes']['price-quote'])) {
                foreach ($response['price-quotes']['price-quote'] as $service) {
                    $serviceHandle = $this->_getServiceHandle($service['service-code']);

                    $this->_rates[$serviceHandle] = [
                        'amount' => (float)$service['price-details']['due'] ?? '',
                        'options' => $service,
                    ];
                }
            } else {
                Provider::error($this, 'Response error: `' . json_encode($response) . '`.');
            }

            // Allow rate modification via events
            $modifyRatesEvent = new ModifyRatesEvent([
                'rates' => $this->_rates,
                'response' => $response,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;

        } catch (\Throwable $e) {
            if ($e->hasResponse()) {
                $data = $this->_parseResponse($e->getResponse());

                if (isset($data['messages']['message']['description'])) {
                    Provider::error($this, 'API error: `' . $data['messages']['message']['description'] . '`.');
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
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://ct.soa-gw.canadapost.ca',
                'auth' => [
                    $this->settings['username'], $this->settings['password']
                ],
                'headers' => [
                    'Content-Type' => 'application/vnd.cpc.ship.rate-v3+xml',
                    'Accept' => 'application/vnd.cpc.ship.rate-v3+xml',
                ]
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        return $this->_parseResponse($response);
    }

    private function _parseResponse($response)
    {
        try {
            // Allow parsing errors to be caught
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string((string)$response->getBody());

            return XmlParser::toArray($xml);
        } catch (\Throwable $e) {
            if ($parseErrors = libxml_get_errors()) {
                Provider::error($this, 'Invalid XML: ' . $parseErrors[0]->message . ': Line #' . $parseErrors[0]->line . '.');
            } else {
                Provider::error($this, 'Request error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
            }
        }
    }

    private function _getServiceHandle($value)
    {
        return str_replace('.', '_', $value);
    }
}
