<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;

use Craft;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;

use Cake\Utility\Xml;

class TNT extends Provider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'kg';
    public $dimensionUnit = 'cm';

    
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'TNT');
    }

    public function getServiceList(): array
    {
        return [
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
            $payload = '<?xml version="1.0" encoding="UTF-8"?>
                <priceRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <appId>PC</appId>
                    <appVersion>3.0</appVersion>
                    <priceCheck>
                        <rateId>rate1</rateId>
                        <sender>
                            <country>' . $storeLocation->country->name .'</country>
                            <town>' . $storeLocation->city . '</town>
                            <postcode>' . $storeLocation->zipCode . '</postcode>
                        </sender>
                        <delivery>
                            <country>' . $order->shippingAddress->country->name . '</country>
                            <town>' . $order->shippingAddress->city . '</town>
                            <postcode>' . $order->shippingAddress->zipCode . '</postcode>
                        </delivery>
                        <collectionDateTime>' . $nextDate[0] . '</collectionDateTime>
                        <product>
                            <id>15N</id>
                            <type>N</type>
                        </product>
                        <account>
                            <accountNumber>' . $this->getSetting('accountNumber') .'</accountNumber>
                            <accountCountry>' . $storeLocation->country->name .'</accountCountry>
                        </account>
                        <currency>' . $order->currency .'</currency>
                        <priceBreakDown>false</priceBreakDown>
                        <consignmentDetails>
                            <totalWeight>' . $dimensions['weight'] .'</totalWeight>
                            <totalVolume>' . $volume .'</totalVolume>
                            <totalNumberOfPieces>1</totalNumberOfPieces>
                        </consignmentDetails>
                        <pieceLine>
                            <numberOfPieces>1</numberOfPieces>
                            <pieceMeasurements>
                                <length>' . $dimensions['length'] .'</length>
                                <width>' . $dimensions['width'] .'</width>
                                <height>' . $dimensions['height'] .'</height>
                                <weight>' . $dimensions['weight'] .'</weight>
                            </pieceMeasurements>
                            <pallet>0</pallet>
                        </pieceLine>
                    </priceCheck>
                </priceRequest>';

            $this->beforeSendPayload($this, $payload, $order);

            $response = $this->_request('POST', 'expressconnect/pricing/getprice', ['body' => $payload]);

        } catch (\Throwable $e) {
            Provider::error($this, Craft::t('postie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->_rates;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient([
                'base_uri' => 'https://express.tnt.com',
                'auth' => [
                    $this->getSetting('username'), $this->getSetting('password')
                ],
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
