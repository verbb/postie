<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Cake\Utility\Xml as XmlParser;

use DomDocument;
use Throwable;

use GuzzleHttp\Client;

class CanadaPost extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Canada Post');
    }

    public static function getServiceList(): array
    {
        return [
            // Domestic
            'DOM_RP' => 'Regular Parcel',
            'DOM_EP' => 'Expedited Parcel',
            'DOM_XP' => 'Xpresspost',
            'DOM_PC' => 'Priority',
            'DOM_LIB' => 'Library Books',

            // USA
            'USA_EP' => 'Expedited Parcel USA',
            'USA_TP' => 'Tracked Packet - USA',

            'USA_TP_LVM' => 'Tracked Packet USA (LVM)',
            'USA_PW_ENV' => 'Priority Worldwide Envelope USA',
            'USA_PW_PAK' => 'Priority Worldwide pak USA',

            'USA_PW_PARCEL' => 'Priority Worldwide parcel USA',
            'USA_SP_AIR' => 'Small Packet USA Air',
            'USA_SP_AIR_LVM' => 'Tracked Packet USA (LVM)',
            'USA_XP' => 'Xpresspost USA',

            // International
            'INT_XP' => 'Xpresspost International',
            'INT_TP' => 'Tracked Packet - International',
            'INT_IP_AIR' => 'International Parcel Air',
            'INT_IP_SURF' => 'International Parcel Surface',
            'INT_PW_ENV' => 'Priority Worldwide envelope INTL',
            'INT_PW_PAK' => 'Priority Worldwide pak INTL',
            'INT_PW_PARCEL' => 'Priority Worldwide parcel INTL',
            'INT_SP_AIR' => 'Small Packet International Air',
            'INT_SP_SURF' => 'Small Packet International Surface',
        ];
    }


    // Properties
    // =========================================================================

    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg';

    private int $maxWeight = 30000; // 30kg


    // Public Methods
    // =========================================================================

    public function getMaxPackageWeight($order): ?int
    {
        return $this->maxWeight;
    }

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
        // $storeLocation->postalCode = 'K1H 7S5'; 
        // $order->shippingAddress->postalCode = 'K1H 7S5';
        // $dimensions['weight'] = 0.45359237;
        //
        //
        //

        // Remove spaces in zip code
        $originPostalCode = str_replace(' ', '', $storeLocation->postalCode);
        $orderPostalCode = str_replace(' ', '', $order->shippingAddress->postalCode);

        $countryCode = $order->shippingAddress->countryCode ?? '';

        try {
            $optionsXml = '';

            if ($additionalOptions = $this->getSetting('additionalOptions')) {
                foreach ($additionalOptions as $option) {
                    $optionsXml .= '<option>';
                    $optionsXml .= '<option-code>' . $option . '</option-code>';

                    if ($option === 'COV') {
                        $optionsXml .= '<option-amount>' . $packedBoxes->getTotalPrice() . '</option-amount>';
                    }

                    $optionsXml .= '</option>';
                }

                if ($optionsXml) {
                    $optionsXml = '<options>' . $optionsXml . '</options>';
                }
            }

            $destinationXml = '<international>
                <country-code>' . $countryCode . '</country-code>
            </international>';

            if ($countryCode === 'CA') {
                $destinationXml = '<domestic>
                    <postal-code>' . $orderPostalCode . '</postal-code>
                </domestic>';
            } else if ($countryCode === 'US') {
                $destinationXml = '<united-states>
                    <zip-code>' . $orderPostalCode . '</zip-code>
                </united-states>';
            }

            $payload = '<?xml version="1.0" encoding="UTF-8"?>
                <mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v3">
                    <customer-number>' . $this->getSetting('customerNumber') . '</customer-number>
                    <parcel-characteristics>
                        <weight>' . $packedBoxes->getTotalWeight() . '</weight>
                    </parcel-characteristics>
                    ' . $optionsXml . '
                    <origin-postal-code>' . $originpostalCode . '</origin-postal-code>
                    <destination>
                        ' . $destinationXml . '
                    </destination>
                </mailing-scenario>';

            // Pretty the output just so it's easier to debug
            $payload = $this->_formatXml($payload);

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'rs/ship/price', ['body' => $payload]);

            if (isset($response['price-quotes']['price-quote'])) {
                foreach ($response['price-quotes']['price-quote'] as $service) {
                    $serviceHandle = $this->_getServiceHandle($service['service-code']);

                    $this->_rates[$serviceHandle] = [
                        'amount' => (float)($service['price-details']['due'] ?? 0),
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
        } catch (Throwable $e) {
            if (method_exists($e, 'hasResponse')) {
                $data = $this->_parseResponse($e->getResponse());
                $message = $data['messages']['message']['description'] ?? $e->getMessage();

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
            $sender = TestingHelper::getTestAddress('CA', ['locality' => 'Toronto']);
            $recipient = TestingHelper::getTestAddress('CA', ['locality' => 'Montreal']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Remove spaces in zip code
            $originPostalCode = str_replace(' ', '', $sender->postalCode);
            $orderPostalCode = str_replace(' ', '', $recipient->postalCode);

            // Create a test payload
            $payload = '<?xml version="1.0" encoding="UTF-8"?>
                <mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v3">
                    <customer-number>' . $this->getSetting('customerNumber') . '</customer-number>
                    <parcel-characteristics>
                        <weight>' . $packedBox['weight'] . '</weight>
                    </parcel-characteristics>
                    <origin-postal-code>' . $originPostalCode . '</origin-postal-code>
                    <destination>
                        <domestic>
                            <postal-code>' . $orderPostalCode . '</postal-code>
                        </domestic>
                    </destination>
                </mailing-scenario>';

            $response = $this->_request('POST', 'rs/ship/price', ['body' => $payload]);
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
        $useTestEndpoint = $this->getSetting('useTestEndpoint') ?? false;

        if ($useTestEndpoint) {
            $baseUri = 'https://ct.soa-gw.canadapost.ca';
        } else {
            $baseUri = 'https://soa-gw.canadapost.ca';
        }

        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => $baseUri,
                'auth' => [
                    $this->getSetting('username'), $this->getSetting('password'),
                ],
                'headers' => [
                    'Content-Type' => 'application/vnd.cpc.ship.rate-v3+xml',
                    'Accept' => 'application/vnd.cpc.ship.rate-v3+xml',
                ],
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = []): ?array
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        return $this->_parseResponse($response);
    }

    private function _parseResponse($response): ?array
    {
        try {
            // Allow parsing errors to be caught
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string((string)$response->getBody());

            return XmlParser::toArray($xml);
        } catch (Throwable $e) {
            if ($parseErrors = libxml_get_errors()) {
                Provider::error($this, 'Invalid XML: ' . $parseErrors[0]->message . ': Line #' . $parseErrors[0]->line . '.');
            } else {
                Provider::error($this, 'Request error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
            }
        }

        return null;
    }

    private function _getServiceHandle($value): array|string
    {
        return str_replace('.', '_', $value);
    }

    private function _formatXml($payload): bool|string
    {
        $doc = new DomDocument('1.0');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML(simplexml_load_string($payload)->asXML());
        return $doc->saveXML();
    }
}
