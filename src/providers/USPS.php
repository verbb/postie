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

    public function getServiceList(): array
    {
        return [
            // Domestic
            'PRIORITY_MAIL_EXPRESS_1_DAY'                                                   => 'USPS Priority Mail Express 1-Day',
            'PRIORITY_MAIL_EXPRESS_1_DAY_HOLD_FOR_PICKUP'                                   => 'USPS Priority Mail Express 1-Day Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_SUNDAY_HOLIDAY_DELIVERY'                           => 'USPS Priority Mail Express 1-Day Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE'                                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'        => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'                          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'  => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Sunday/Holiday Delivery',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE'                         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Sunday/Holiday Delivery',

            'PRIORITY_MAIL_EXPRESS_2_DAY'                                           => 'USPS Priority Mail Express 2-Day',
            'PRIORITY_MAIL_EXPRESS_2_DAY_HOLD_FOR_PICKUP'                           => 'USPS Priority Mail Express 2-Day Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE'                        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE'                  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope Hold For Pickup',
            'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE'                 => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope Hold For Pickup',

            'PRIORITY_MAIL_1_DAY'                              => 'USPS Priority Mail 1-Day',
            'PRIORITY_MAIL_1_DAY_LARGE_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Large Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_MEDIUM_FLAT_RATE_BOX'         => 'USPS Priority Mail 1-Day Medium Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Small Flat Rate Box',
            'PRIORITY_MAIL_1_DAY_FLAT_RATE_ENVELOPE'           => 'USPS Priority Mail 1-Day Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_PADDED_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail 1-Day Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Small Flat Rate Envelope',
            'PRIORITY_MAIL_1_DAY_WINDOW_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Window Flat Rate Envelope',

            'PRIORITY_MAIL_2_DAY'                              => 'USPS Priority Mail 2-Day',
            'PRIORITY_MAIL_2_DAY_LARGE_FLAT_RATE_BOX'          => 'USPS Priority Mail 2-Day Large Flat Rate Box',
            'PRIORITY_MAIL_2_DAY_MEDIUM_FLAT_RATE_BOX'         => 'USPS Priority Mail 2-Day Medium Flat Rate Box',
            'PRIORITY_MAIL_2_DAY_SMALL_FLAT_RATE_BOX'          => 'USPS Priority Mail 2-Day Small Flat Rate Box',
            'PRIORITY_MAIL_2_DAY_FLAT_RATE_ENVELOPE'           => 'USPS Priority Mail 2-Day Flat Rate Envelope',
            'PRIORITY_MAIL_2_DAY_LEGAL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 2-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_2_DAY_PADDED_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 2-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_2_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail 2-Day Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_2_DAY_SMALL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 2-Day Small Flat Rate Envelope',
            'PRIORITY_MAIL_2_DAY_WINDOW_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 2-Day Window Flat Rate Envelope',

            'PRIORITY_MAIL_3_DAY'                              => 'USPS Priority Mail 3-Day',
            'PRIORITY_MAIL_3_DAY_LARGE_FLAT_RATE_BOX'          => 'USPS Priority Mail 3-Day Large Flat Rate Box',
            'PRIORITY_MAIL_3_DAY_MEDIUM_FLAT_RATE_BOX'         => 'USPS Priority Mail 3-Day Medium Flat Rate Box',
            'PRIORITY_MAIL_3_DAY_SMALL_FLAT_RATE_BOX'          => 'USPS Priority Mail 3-Day Small Flat Rate Box',
            'PRIORITY_MAIL_3_DAY_FLAT_RATE_ENVELOPE'           => 'USPS Priority Mail 3-Day Flat Rate Envelope',
            'PRIORITY_MAIL_3_DAY_LEGAL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 3-Day Legal Flat Rate Envelope',
            'PRIORITY_MAIL_3_DAY_PADDED_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 3-Day Padded Flat Rate Envelope',
            'PRIORITY_MAIL_3_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail 3-Day Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_3_DAY_SMALL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 3-Day Small Flat Rate Envelope',
            'PRIORITY_MAIL_3_DAY_WINDOW_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 3-Day Window Flat Rate Envelope',

            'FIRST_CLASS_MAIL'                   => 'USPS First-Class Mail',
            'FIRST_CLASS_MAIL_STAMPED_LETTER'    => 'USPS First-Class Mail Stamped Letter',
            'FIRST_CLASS_MAIL_METERED_LETTER'    => 'USPS First-Class Mail Metered Letter',
            'FIRST_CLASS_MAIL_LARGE_ENVELOPE'    => 'USPS First-Class Mail Large Envelope',
            'FIRST_CLASS_MAIL_POSTCARDS'         => 'USPS First-Class Mail Postcards',
            'FIRST_CLASS_MAIL_LARGE_POSTCARDS'   => 'USPS First-Class Mail Large Postcards',
            'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => 'USPS First-Class Package Service - Retail',

            'MEDIA_MAIL_PARCEL'   => 'USPS Media Mail Parcel',
            'LIBRARY_MAIL_PARCEL' => 'USPS Library Mail Parcel',

            // International
            'USPS_GXG_ENVELOPES'                  => 'USPS Global Express Guaranteed Envelopes',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'USPS Priority Mail Express International',

            'PRIORITY_MAIL_INTERNATIONAL'                      => 'USPS Priority Mail International',
            'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX'  => 'USPS Priority Mail International Large Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail International Medium Flat Rate Box',

            'FIRST_CLASS_MAIL_INTERNATIONAL'            => 'USPS First-Class Mail International',
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
        $packedBoxes = $this->packOrder($order)->getSerializedPackedBoxList();

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
            if ($order->shippingAddress->country->iso == 'US') {
                Provider::log($this, 'Domestic rate service call');

                foreach ($packedBoxes as $packedBox) {
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

                foreach ($packedBoxes as $packedBox) {
                    $package = new RatePackage();
                    $package->setPounds($packedBox['weight']);
                    $package->setOunces(0);
                    $package->setField('Machinable', 'True');
                    $package->setField('MailType', 'Package');

                    // value of content necessary for export
                    $package->setField('ValueOfContents', $order->getTotalSaleAmount());
                    $package->setField('Country', $order->shippingAddress->country->name);

                    // Check if dimensions greater then 12 inches then set LARGE package
                    if ($packedBox['width'] > 12 || $packedBox['height'] > 12 || $packedBox['length'] > 12) {
                        $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);
                        $package->setField('Size', 'LARGE');
                    } else {
                        $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);
                        $package->setField('Size', 'REGULAR');
                    }

                    $package->setField('Width', $packedBox['width']);
                    $package->setField('Length', $packedBox['height']);
                    $package->setField('Height', $packedBox['length']);
                    // Girth are relevant when CONTAINER_NONRECTANGULAR
                    $package->setField('Girth', $packedBox['width'] * 2 + $packedBox['length'] * 2);

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

            if (isset($response['RateV4Response']['Package']['Postage'])) {
                foreach ($response['RateV4Response']['Package']['Postage'] as $service) {
                    $serviceHandle = $this->_getServiceHandle($service['MailService']);

                    $this->_rates[$serviceHandle] = [
                        'amount' => $service['Rate'],
                        'options' => [
                            'mailService' => $service['MailService'],
                            'zipOrigination' => $response['RateV4Response']['Package']['ZipOrigination'] ?? '',
                            'zipDestination' => $response['RateV4Response']['Package']['ZipDestination'] ?? '',
                            'pounds' => $response['RateV4Response']['Package']['Pounds'] ?? '',
                            'ounces' => $response['RateV4Response']['Package']['Ounces'] ?? '',
                            'size' => $response['RateV4Response']['Package']['Size'] ?? '',
                            'machinable' => $response['RateV4Response']['Package']['Machinable'] ?? '',
                            'zone' => $response['RateV4Response']['Package']['Zone'] ?? '',
                        ],
                    ];
                }
            } else if (isset($response['RateV4Response']['Package']['Error'])) {
                Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
                    'json' => Json::encode($response['RateV4Response']['Package']['Error']),
                ]));
            } else {
                if (isset($response['IntlRateV2Response']['Package']['Service'])) {
                    foreach ($response['IntlRateV2Response']['Package']['Service'] as $service) {
                        $serviceHandle = $this->_getServiceHandle($service['SvcDescription']);

                        $this->_rates[$serviceHandle] = [
                            'amount' => $service['Postage'],
                            'options' => $service,
                        ];
                    }
                } else if (isset($response['IntlRateV2Response']['Package']['Error'])) {
                    Provider::error($this, Craft::t('postie', 'Response error: `{json}`.', [
                        'json' => Json::encode($response['IntlRateV2Response']['Package']['Error']),
                    ]));
                } else {
                    Provider::error($this, 'No Services found.');
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

    private function _getServiceHandle($string)
    {
        $replace = [
            '&lt;sup&gt;&#8482;&lt;/sup&gt;',
            '&lt;sup&gt;&#174;&lt;/sup&gt;',
        ];

        $string = str_replace($replace, '', $string);
        $string = StringHelper::toSnakeCase($string);
        $string = strtoupper($string);

        return $string;
    }

    private function _parseZipCode($zip)
    {
        $zip = explode('-', $zip)[0] ?? $zip;

        return $zip;
    }
}
