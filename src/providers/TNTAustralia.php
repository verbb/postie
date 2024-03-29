<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Cake\Utility\Xml;

use GuzzleHttp\Client;

use DateTime;
use DomDocument;
use SimpleXMLElement;
use Throwable;

class TNTAustralia extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'TNT Australia');
    }

    public static function getServiceList(): array
    {
        return [
            'EX10' => '10:00 Express',
            'EX12' => '12:00 Express',
            '712' => '9:00 Express',
            '717' => 'Sensitive Express',
            '717B' => 'Sensitive Express',
            '73' => 'Overnight PAYU Satchel',
            '75' => 'Overnight Express',
            '76' => 'Road Express',
            '718' => 'Fashion Express',
            '701' => 'National Same day',
        ];
    }
    

    // Properties
    // =========================================================================

    public ?string $handle = 'tntAustralia';
    public string $dimensionUnit = 'cm';
    public string $weightUnit = 'kg';

    private int $maxDomesticWeight = 70000; // 70kg
    private int $maxInternationalWeight = 500000; // 500kg


    // Public Methods
    // =========================================================================

    public function getMaxPackageWeight($order): ?int
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
    }

    public function fetchShippingRates($order): ?array
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


        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $dimensions, $order);

        $nextDate = $this->_numberOfWorkingDates(date('Y-m-d'), 1);

        try {
            $packagesXml = '';

            foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                $packagesXml .= '<packageLine>
                    <numberOfPackages>1</numberOfPackages>
                    <dimensions unit="cm">
                        <length>' . ceil($packedBox['length']) . '</length>
                        <width>' . ceil($packedBox['width']) . '</width>
                        <height>' . ceil($packedBox['height']) . '</height>
                    </dimensions>
                    <weight unit="kg">
                        <weight>' . ceil($packedBox['weight']) . '</weight>
                    </weight>
                </packageLine>';
            }

            $xmlRequest = '<?xml version="1.0"?>
                <enquiry xmlns="http://www.tntexpress.com.au">
                    <ratedTransitTimeEnquiry>
                        <cutOffTimeEnquiry>
                            <collectionAddress>
                                <suburb>' . $storeLocation->locality . '</suburb>
                                <postCode>' . $storeLocation->postalCode . '</postCode>
                                <state>' . $storeLocation->administrativeArea . '</state>
                            </collectionAddress>
                            <deliveryAddress>
                                <suburb>' . $order->shippingAddress->locality . '</suburb>
                                <postCode>' . $order->shippingAddress->postalCode . '</postCode>
                                <state>' . $order->shippingAddress->administrativeArea . '</state>
                            </deliveryAddress>
                            <shippingDate>' . $nextDate[0] . '</shippingDate>
                            <userCurrentLocalDateTime>' . date('Y-m-d\TH:i:s') . '</userCurrentLocalDateTime>
                            <dangerousGoods>
                                <dangerous>false</dangerous>
                            </dangerousGoods>
                            <packageLines packageType="N">' . $packagesXml . '</packageLines>
                        </cutOffTimeEnquiry>
                        <termsOfPayment>
                            <senderAccount>' . $this->getSetting('accountNumber') . '</senderAccount>
                            <payer>S</payer>
                        </termsOfPayment>
                    </ratedTransitTimeEnquiry>
                </enquiry>';

            // Pretty the output just so it's easier to debug
            $xmlRequest = $this->_formatXml($xmlRequest);

            $payload = [
                'Username' => $this->getSetting('username'),
                'Password' => $this->getSetting('password'),
                'XMLRequest' => $xmlRequest,
            ];

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'Rtt/inputRequest.asp', ['form_params' => $payload]);
            $response = Json::decode(Json::encode($response));

            if (isset($response['ratedTransitTimeResponse']['ratedProducts']['ratedProduct'])) {
                foreach ($response['ratedTransitTimeResponse']['ratedProducts']['ratedProduct'] as $service) {
                    $this->_rates[$service['product']['code']] = [
                        'amount' => (float)($service['quote']['price'] ?? 0),
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
            $sender = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
            $recipient = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $xmlRequest = '<?xml version="1.0"?>
                <enquiry xmlns="http://www.tntexpress.com.au">
                    <ratedTransitTimeEnquiry>
                        <cutOffTimeEnquiry>
                            <collectionAddress>
                                <suburb>' . $sender->locality . '</suburb>
                                <postCode>' . $sender->postalCode . '</postCode>
                                <state>' . $sender->administrativeArea . '</state>
                            </collectionAddress>
                            <deliveryAddress>
                                <suburb>' . $recipient->locality . '</suburb>
                                <postCode>' . $recipient->postalCode . '</postCode>
                                <state>' . $recipient->administrativeArea . '</state>
                            </deliveryAddress>
                            <dangerousGoods>
                                <dangerous>false</dangerous>
                            </dangerousGoods>
                            <packageLines packageType="N">
                                <packageLine>
                                    <numberOfPackages>1</numberOfPackages>
                                    <dimensions unit="cm">
                                        <length>' . $packedBox['length'] . '</length>
                                        <width>' . $packedBox['width'] . '</width>
                                        <height>' . $packedBox['height'] . '</height>
                                    </dimensions>
                                    <weight unit="kg">
                                        <weight>' . $packedBox['weight'] . '</weight>
                                    </weight>
                                </packageLine>
                            </packageLines>
                        </cutOffTimeEnquiry>
                        <termsOfPayment>
                            <senderAccount>' . $this->getSetting('accountNumber') . '</senderAccount>
                            <payer>S</payer>
                        </termsOfPayment>
                    </ratedTransitTimeEnquiry>
                </enquiry>';

            $payload = [
                'Username' => $this->getSetting('username'),
                'Password' => $this->getSetting('password'),
                'XMLRequest' => $xmlRequest,
            ];

            $response = $this->_request('POST', 'Rtt/inputRequest.asp', ['form_params' => $payload]);
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
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://www.tntexpress.com.au',
                'headers' => [
                    'Content-Type' => 'application/xml',
                ],
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = []): DOMDocument|SimpleXMLElement
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        $string = (string)$response->getBody();
        $xml = simplexml_load_string($string);

        return Xml::build($xml->asXml());
    }

    private function _formatXml($payload): bool|string
    {
        $doc = new DomDocument('1.0');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML(simplexml_load_string($payload)->asXML());
        return $doc->saveXML();
    }

    private function _numberOfWorkingDates($from, $days): array
    {
        $workingDays = [1, 2, 3, 4, 5];
        $holidayDays = ['*-12-25', '*-12-26', '*-12-27', '*-12-28', '*-12-29', '*-12-30', '*-12-31', '*-01-01', '*-01-02', '*-01-03', '*-01-04', '*-01-05', '*-01-26'];

        $from = new DateTime($from);
        $dates = [];

        while ($days) {
            $from->modify('+1 day');

            if (!in_array($from->format('N'), $workingDays)) {
                continue;
            }

            if (in_array($from->format('Y-m-d'), $holidayDays)) {
                continue;
            }

            if (in_array($from->format('*-m-d'), $holidayDays)) {
                continue;
            }

            $dates[] = $from->format('Y-m-d');
            $days--;
        }

        return $dates;
    }
}
