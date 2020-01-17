<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Cake\Utility\Xml;

class TNTAustralia extends Provider
{
    // Properties
    // =========================================================================

    public $name = 'TNT Australia';


    // Public Methods
    // =========================================================================

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('postie/providers/tnt-au', ['provider' => $this]);
    }

    public function getServiceList(): array
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

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();
        $dimensions = $this->getDimensions($order, 'kg', 'cm');
        $volume = $dimensions['width'] * $dimensions['height'] * $dimensions['length'];
        $nextDate = $this->_numberOfWorkingDates(date('Y-m-d'), 1);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $dimensions, $order);

        try {
            $xmlRequest = '<?xml version="1.0"?>
                <enquiry xmlns="http://www.tntexpress.com.au">
                    <ratedTransitTimeEnquiry>
                        <cutOffTimeEnquiry>
                            <collectionAddress>
                                <suburb>' . $storeLocation->city . '</suburb>
                                <postCode>' . $storeLocation->zipCode . '</postCode>
                                <state>' . $storeLocation->state . '</state>
                            </collectionAddress>
                            <deliveryAddress>
                                <suburb>' . $order->shippingAddress->city .'</suburb>
                                <postCode>' . $order->shippingAddress->zipCode .'</postCode>
                                <state>' . $order->shippingAddress->state .'</state>
                            </deliveryAddress>
                            <shippingDate>' . $nextDate[0] . '</shippingDate>
                            <userCurrentLocalDateTime>' . date('Y-m-d\TH:i:s') . '</userCurrentLocalDateTime>
                            <dangerousGoods>
                                <dangerous>false</dangerous>
                            </dangerousGoods>
                            <packageLines packageType="N">
                                <packageLine>
                                    <numberOfPackages>1</numberOfPackages>
                                    <dimensions unit="cm">
                                        <length>' . $dimensions['length'] . '</length>
                                        <width>' . $dimensions['width'] . '</width>
                                        <height>' . $dimensions['height'] . '</height>
                                    </dimensions>
                                    <weight unit="kg">
                                        <weight>' . $dimensions['weight'] .'</weight>
                                    </weight>
                                </packageLine>
                            </packageLines>
                        </cutOffTimeEnquiry>
                        <termsOfPayment>
                            <senderAccount>' . $this->settings['accountNumber'] . '</senderAccount>
                            <payer>S</payer>
                        </termsOfPayment>
                    </ratedTransitTimeEnquiry>
                </enquiry>';

            $params = [
                'Username' => $this->settings['username'],
                'Password' => $this->settings['password'],
                'XMLRequest' => $xmlRequest,
            ];

            $response = $this->_request('POST', 'Rtt/inputRequest.asp', ['form_params' => $params]);

            if (isset($response['response']['ratedTransitTimeResponse']['ratedProducts']['ratedProduct'])) {
                foreach ($response['response']['ratedTransitTimeResponse']['ratedProducts']['ratedProduct'] as $service) {
                    $this->_rates[$service['product']['code']] = [
                        'amount' => (float)$service['quote']['price'] ?? '',
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
                'order' => $order,
            ]);

            if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
                $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
            }

            $this->_rates = $modifyRatesEvent->rates;

        } catch (\Throwable $e) {
            Provider::error($this, 'API error: `' . $e->getMessage() . ':' . $e->getLine() . '`.');
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://www.tntexpress.com.au',
                // 'auth' => [
                //     $this->settings['username'], $this->settings['password']
                // ],
                'headers' => [
                    'Content-Type' => 'application/xml',
                ]
            ]);
        }

        return $this->_client;
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, $uri, $options);

        $string = (string)$response->getBody();
        $xml = simplexml_load_string($string);

        return Xml::build($xml->asXml());
    }

    private function _numberOfWorkingDates($from, $days) {
        $workingDays = [1, 2, 3, 4, 5];
        $holidayDays = ['*-12-25', '*-12-26', '*-12-27', '*-12-28', '*-12-29', '*-12-30', '*-12-31', '*-01-01', '*-01-02', '*-01-03', '*-01-04', '*-01-05', '*-01-26'];

        $from = new \DateTime($from);
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
