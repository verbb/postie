<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use craft\commerce\Plugin as Commerce;

use USPS\Rate;
use USPS\RatePackage;

class USPS extends Provider
{
    // Properties
    // =========================================================================

    public $handle = 'usps';
    public $weightUnit = 'lb';
    public $dimensionUnit = 'in';

    private $maxDomesticWeight = 31751.5; // 70lbs
    private $maxInternationalWeight = 9071.85; // 20lbs


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'USPS');
    }

    public static function isDomestic($countryCode): bool
    {
        $domestic = ['US', 'PR', 'VI', 'MH', 'FM', 'GU', 'MP', 'AS', 'UM'];

        return in_array($countryCode, $domestic);
    }

    public function getServiceList(): array
    {
        return [
            // Domestic
            'PRIORITY_MAIL_EXPRESS' => 'USPS Priority Mail Express',
            'PRIORITY_MAIL_EXPRESS_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express Legal Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express Legal Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express Padded Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express Padded Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express Padded Flat Rate Envelope Sunday/Holiday Delivery',

            'PRIORITY_MAIL' => 'USPS Priority Mail',
            'PRIORITY_MAIL_LARGE_FLAT_RATE_BOX' => 'USPS Priority Mail Large Flat Rate Box',
            'PRIORITY_MAIL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail Medium Flat Rate Box',
            'PRIORITY_MAIL_SMALL_FLAT_RATE_BOX' => 'USPS Priority Mail Small Flat Rate Box',
            'PRIORITY_MAIL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Flat Rate Envelope',
            'PRIORITY_MAIL_LEGAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Legal Flat Rate Envelope',
            'PRIORITY_MAIL_PADDED_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Padded Flat Rate Envelope',
            'PRIORITY_MAIL_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_SMALL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Small Flat Rate Envelope',
            'PRIORITY_MAIL_WINDOW_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Window Flat Rate Envelope',

            'FIRST_CLASS_MAIL' => 'USPS First-Class Mail',
            'FIRST_CLASS_MAIL_STAMPED_LETTER' => 'USPS First-Class Mail Stamped Letter',
            'FIRST_CLASS_MAIL_METERED_LETTER' => 'USPS First-Class Mail Metered Letter',
            'FIRST_CLASS_MAIL_LARGE_ENVELOPE' => 'USPS First-Class Mail Large Envelope',
            'FIRST_CLASS_MAIL_POSTCARDS' => 'USPS First-Class Mail Postcards',
            'FIRST_CLASS_MAIL_LARGE_POSTCARDS' => 'USPS First-Class Mail Large Postcards',
            'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => 'USPS First-Class Package Service - Retail',

            'STANDARD_PARCEL_POST' => 'USPS Standard Parcel Post',
            'MEDIA_MAIL_PARCEL' => 'USPS Media Mail Parcel',
            'LIBRARY_MAIL_PARCEL' => 'USPS Library Mail Parcel',

            // International
            'USPS_GXG_ENVELOPES' => 'USPS Global Express Guaranteed Envelopes',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'USPS Priority Mail Express International',

            'PRIORITY_MAIL_INTERNATIONAL' => 'USPS Priority Mail International',
            'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX' => 'USPS Priority Mail International Large Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail International Medium Flat Rate Box',

            'FIRST_CLASS_MAIL_INTERNATIONAL' => 'USPS First-Class Mail International',
            'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => 'USPS First-Class Package International Service',
        ];
    }

    public function getMaxPackageWeight($order)
    {
        if ($this->getIsInternational($order)) {
            return $this->maxInternationalWeight;
        }

        return $this->maxDomesticWeight;
    }

    public function fetchShippingRates($order)
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $client = $this->_getClient();

        if (!$client) {
            Provider::error($this, 'Unable to communicate with API.');
            return false;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

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

        // // International
        // // $country = Commerce::getInstance()->countries->getCountryByIso('CA');
        // // $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, 'ON');

        // // $order->shippingAddress->address1 = '80 Spadina Ave';
        // // $order->shippingAddress->city = 'Toronto';
        // // $order->shippingAddress->zipCode = 'M5V 2J4';
        // // $order->shippingAddress->stateId = $state->id;
        // // $order->shippingAddress->countryId = $country->id;
        //
        //
        //

        try {
            $countryIso = $order->shippingAddress->country->iso ?? '';

            if (self::isDomestic($countryIso)) {
                Provider::log($this, 'Domestic rate service call');

                foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                    // Create new package object and assign the properties
                    // apparently the order you assign them is important so make sure
                    // to set them as the example below
                    // set the RatePackage for more info about the constants
                    $package = new RatePackage();

                    // Set service
                    $package->setService(RatePackage::SERVICE_ALL);
                    $package->setFirstClassMailType(RatePackage::MAIL_TYPE_PARCEL);

                    $package->setZipOrigination($this->_parseZipCode($storeLocation->zipCode));
                    $package->setZipDestination($this->_parseZipCode($order->shippingAddress->zipCode));
                    $package->setPounds($packedBox['weight']);
                    $package->setOunces(0);
                    $package->setContainer('');
                    $package->setSize(RatePackage::SIZE_REGULAR);
                    $package->setField('Machinable', true);

                    // add the package to the client stack
                    $client->addPackage($package);
                }
            } else {
                Provider::log($this, 'International rate service call');

                // Set international flag
                $client->setInternationalCall(true);
                $client->addExtraOption('Revision', 2);

                foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                    $package = new RatePackage();
                    $package->setPounds($packedBox['weight']);
                    $package->setOunces(0);
                    $package->setField('Machinable', 'True');
                    $package->setField('MailType', 'Package');
                    $package->setField('ValueOfContents', $order->getTotalSaleAmount());
                    $package->setField('Country', $order->shippingAddress->country->name);

                    $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);

                    // Mismatched on purpose!
                    $package->setField('Width', $packedBox['height']);
                    $package->setField('Length', $packedBox['width']);
                    $package->setField('Height', $packedBox['length']);
                    $package->setField('Girth', round($packedBox['length'] * 2 + $packedBox['height'] * 2));

                    $package->setField('OriginZip', $this->_parseZipCode($storeLocation->zipCode));
                    $package->setField('CommercialFlag', 'N');

                    if ($order->shippingAddress->zipCode) {
                        $package->setField('AcceptanceDateTime', DateTimeHelper::toIso8601(time()));
                        $package->setField('DestinationPostalCode', $this->_parseZipCode($order->shippingAddress->zipCode));
                    }

                    // add the package to the client stack
                    $client->addPackage($package);
                }
            }

            $this->beforeSendPayload($this, $client, $order);

            // Perform the request
            $client->getRate();

            $response = $client->getArrayResponse();

            // Check for general errors
            if (isset($response['Error'])) {
                Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
                    'json' => Json::encode($response['Error']),
                ]));

                return $this->_rates;
            }

            // Responses will be different depending on domestic/international
            if (self::isDomestic($countryIso)) {
                $packages = $response['RateV4Response']['Package'] ?? [];
            } else {
                $packages = $response['IntlRateV2Response']['Package'] ?? [];
            }

            // Normalise against multiple packages. The API returns rates for each package, as opposed to totals
            if ($packages && !isset($packages[0])) {
                $packages = [$packages];
            }

            foreach ($packages as $package) {
                // Service for international, Postage for domestic. Otherwise, largely the same
                $services = $package['Service'] ?? $package['Postage'] ?? [];

                // Check for errors
                if (isset($package['Error'])) {
                    Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
                        'json' => Json::encode($package['Error']),
                    ]));

                    continue;
                }

                if ($services && !isset($services[0])) {
                    $services = [$services];
                }
                foreach ($services as $service) {
                    // ID for international, CLASSID for domestic
                    $serviceHandleKey = $service['@attributes']['CLASSID'] ?? $service['@attributes']['ID'] ?? null;
                    $serviceHandle = $this->_getServiceHandle($serviceHandleKey, $countryIso);

                    // The API returns rates for each package in the request, so combine them before reporting back
                    // Postage for international, Rate for domestic
                    $amount = $service['Postage'] ?? $service['Rate'] ?? 0;

                    if (isset($this->_rates[$serviceHandle]['amount'])) {
                        $amount = $this->_rates[$serviceHandle]['amount'] + $amount;
                    }

                    // Combine the package and service information as options
                    $optionData = array_merge($package, $service);
                    unset($optionData['Service']);
                    unset($optionData['Postage']);

                    $this->_rates[$serviceHandle] = [
                        'amount' => $amount,
                        'options' => $optionData,
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
            $sender = TestingHelper::getTestAddress('US', ['city' => 'Cupertino']);
            $recipient = TestingHelper::getTestAddress('US', ['city' => 'Mountain View']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $client = $this->_getClient();

            $package = new RatePackage();
            $package->setService(RatePackage::SERVICE_ALL);
            $package->setFirstClassMailType(RatePackage::MAIL_TYPE_PARCEL);
            $package->setZipOrigination($sender->zipCode);
            $package->setZipDestination($recipient->zipCode);
            $package->setPounds($packedBox['weight']);
            $package->setOunces(0);
            $package->setContainer('');
            $package->setSize(RatePackage::SIZE_REGULAR);
            $package->setField('Machinable', true);
            $client->addPackage($package);

            $client->getRate();
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
            $username = $this->getSetting('username');

            $this->_client = new Rate($username);
        }

        return $this->_client;
    }

    private function _getServiceHandle($code, $countryIso)
    {
        if (self::isDomestic($countryIso)) {
            $services = [
                'PRIORITY_MAIL_EXPRESS' => '3',
                'PRIORITY_MAIL_EXPRESS_SUNDAY_HOLIDAY_DELIVERY' => '23',
                'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE' => '13',
                'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '25',
                'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE' => '30',
                'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '32',
                'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE' => '62',
                'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '64',

                'PRIORITY_MAIL' => '1',
                'PRIORITY_MAIL_LARGE_FLAT_RATE_BOX' => '22',
                'PRIORITY_MAIL_MEDIUM_FLAT_RATE_BOX' => '17',
                'PRIORITY_MAIL_SMALL_FLAT_RATE_BOX' => '28',
                'PRIORITY_MAIL_FLAT_RATE_ENVELOPE' => '16',
                'PRIORITY_MAIL_LEGAL_FLAT_RATE_ENVELOPE' => '44',
                'PRIORITY_MAIL_PADDED_FLAT_RATE_ENVELOPE' => '29',
                'PRIORITY_MAIL_GIFT_CARD_FLAT_RATE_ENVELOPE' => '38',
                'PRIORITY_MAIL_SMALL_FLAT_RATE_ENVELOPE' => '42',
                'PRIORITY_MAIL_WINDOW_FLAT_RATE_ENVELOPE' => '40',

                'FIRST_CLASS_MAIL' => '0D',
                'FIRST_CLASS_MAIL_STAMPED_LETTER' => '12',
                'FIRST_CLASS_MAIL_METERED_LETTER' => '78',
                'FIRST_CLASS_MAIL_LARGE_ENVELOPE' => '0C',
                'FIRST_CLASS_MAIL_POSTCARDS' => '0A',
                'FIRST_CLASS_MAIL_LARGE_POSTCARDS' => '15',
                'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => '61',

                'STANDARD_PARCEL_POST' => '4',
                'MEDIA_MAIL_PARCEL' => '6',
                'LIBRARY_MAIL_PARCEL' => '7',
            ];
        } else {
            $services = [
                'USPS_GXG_ENVELOPES' => '12',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => '1',

                'PRIORITY_MAIL_INTERNATIONAL' => '2',
                'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX' => '18',
                'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => '17',

                'FIRST_CLASS_MAIL_INTERNATIONAL' => '14',
                'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => '15',
            ];
        }

        return array_search($code, $services);
    }

    private function _parseZipCode($zip)
    {
        $zip = explode('-', $zip)[0] ?? $zip;

        return $zip;
    }
}
