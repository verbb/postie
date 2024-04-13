<?php
namespace verbb\postie\providers;

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

use Throwable;

class USPS extends Provider
{
    // Static Methods
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

    public static function getServiceList(): array
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
            'PRIORITY_MAIL_EXPRESS_FLAT_RATE_BOXES' => 'USPS Priority Mail Express Flat Rate Boxes',
            'PRIORITY_MAIL_EXPRESS_SUNDAY_HOLIDAY_DELIVERY_FLAT_RATE_BOXES' => 'USPS Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes',

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
            'PRIORITY_MAIL_REGIONAL_RATE_BOX_A' => 'USPS Priority Mail Regional Rate Box A',
            'PRIORITY_MAIL_REGIONAL_RATE_BOX_B' => 'USPS Priority Mail Regional Rate Box B',
            'PRIORITY_MAIL_REGIONAL_RATE_BOX_C' => 'USPS Priority Mail Regional Rate Box C',

            'FIRST_CLASS_MAIL_LARGE_ENVELOPE' => 'USPS First-Class Mail Large Envelope',
            'FIRST_CLASS_MAIL_LETTER' => 'USPS First-Class Mail Letter',
            'FIRST_CLASS_MAIL_PARCEL' => 'USPS First-Class Mail Parcel',
            'FIRST_CLASS_MAIL_POSTCARDS' => 'USPS First-Class Mail Postcards',
            'FIRST_CLASS_MAIL_LARGE_POSTCARDS' => 'USPS First-Class Mail Large Postcards',
            'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => 'USPS First-Class Package Service - Retail',

            'GROUND_ADVANTAGE' => 'USPS Ground Advantage',
            'GROUND_ADVANTAGE_CUBIC' => 'USPS Ground Advantage Cubic',
            'GROUND_ADVANTAGE_HOLD_FOR_PICKUP' => 'USPS Ground Advantage Hold For Pickup',
            'GROUND_ADVANTAGE_CUBIC_HOLD_FOR_PICKUP' => 'USPS Ground Advantage Cubic Hold For Pickup',
            'GROUND_ADVANTAGE_HAZMAT' => 'USPS Ground Advantage HAZMAT',
            'GROUND_ADVANTAGE_CUBIC_HAZMAT' => 'USPS Ground Advantage Cubic HAZMAT',
            'GROUND_ADVANTAGE_PARCEL_LOCKER' => 'USPS Ground Advantage Parcel Locker',
            'GROUND_ADVANTAGE_CUBIC_PARCEL_LOCKER' => 'USPS Ground Advantage Cubic Parcel Locker',

            'STANDARD_PARCEL_POST' => 'USPS Standard Parcel Post',
            'MEDIA_MAIL_PARCEL' => 'USPS Media Mail Parcel',
            'LIBRARY_MAIL_PARCEL' => 'USPS Library Mail Parcel',

            // International
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'USPS Priority Mail Express International',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express International Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_LEGAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express International Legal Flat Rate Envelope',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_FLAT_RATE_BOXES' => 'USPS Priority Mail Express International Flat Rate Boxes',
            'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_PADDED_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail Express International Padded Flat Rate Envelope',

            'PRIORITY_MAIL_INTERNATIONAL' => 'USPS Priority Mail International',
            'PRIORITY_MAIL_INTERNATIONAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail International Medium Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX' => 'USPS Priority Mail International Large Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_SMALL_FLAT_RATE_BOX' => 'USPS Priority Mail International Small Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Gift Card Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_WINDOW_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Window Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_SMALL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Small Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_LEGAL_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Legal Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_PADDED_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail International Padded Flat Rate Envelope',
            'PRIORITY_MAIL_INTERNATIONAL_DVD_FLAT_RATE_BOX' => 'USPS Priority Mail International DVD Flat Rate Box',
            'PRIORITY_MAIL_INTERNATIONAL_LARGE_VIDEO_FLAT_RATE_BOX' => 'USPS Priority Mail International Large Video Flat Rate Box',

            'FIRST_CLASS_MAIL_INTERNATIONAL' => 'USPS First-Class Mail International',
            'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => 'USPS First-Class Package International Service',
            'FIRST_CLASS_MAIL_INTERNATIONAL_LETTER' => 'USPS First-Class Mail International Letter',
            'FIRST_CLASS_MAIL_INTERNATIONAL_POSTCARD' => 'USPS First-Class Mail International Postcard',

            'USPS_GLOBAL_EXPRESS_GUARANTEED' => 'USPS Global Express Guaranteed (GXG)',
            'USPS_GLOBAL_EXPRESS_GUARANTEED_DOCUMENT' => 'USPS Global Express Guaranteed Document',
            'USPS_GLOBAL_EXPRESS_GUARANTEED_NON_DOCUMENT_RECTANGULAR' => 'USPS Global Express Guaranteed Non-Document Rectangular',
            'USPS_GLOBAL_EXPRESS_GUARANTEED_NON_DOCUMENT_NON_RECTANGULAR' => 'USPS Global Express Guaranteed Non-Document Non-Rectangular',
            'USPS_GXG_ENVELOPES' => 'USPS Global Express Guaranteed Envelopes',
        ];
    }


    // Properties
    // =========================================================================

    public ?string $handle = 'usps';
    public string $dimensionUnit = 'in';
    public string $weightUnit = 'lb'; // 70lbs

    private float $maxDomesticWeight = 31751.5; // 20lbs
    private float $maxInternationalWeight = 9071.85;


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
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $client = $this->_getClient();

        if (!$client) {
            Provider::error($this, 'Unable to communicate with API.');
            return null;
        }

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order);

        // Allow location and dimensions modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        //
        // TESTING
        //
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('US', ['locality' => 'Cupertino']);
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['locality' => 'Mountain View'], $order);

        // Canada
        // $storeLocation = TestingHelper::getTestAddress('CA', ['locality' => 'Toronto']);
        // $order->shippingAddress = TestingHelper::getTestAddress('CA', ['locality' => 'Montreal'], $order);

        // EU
        // $storeLocation = TestingHelper::getTestAddress('GB', ['locality' => 'London']);
        // $order->shippingAddress = TestingHelper::getTestAddress('GB', ['locality' => 'Dunchurch'], $order);
        //
        //
        //

        try {
            $countryCode = $order->shippingAddress->countryCode ?? '';

            if (self::isDomestic($countryCode)) {
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

                    $package->setZipOrigination($this->_parseZipCode($storeLocation->postalCode));
                    $package->setZipDestination($this->_parseZipCode($order->shippingAddress->postalCode));
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

                // We need to fetch the full country name
                $countryName = null;
                $countryInfo = Craft::$app->getAddresses()->getCountryRepository()->get($order->shippingAddress->countryCode);

                if ($countryInfo) {
                    $countryName = $countryInfo->getName();
                }

                foreach ($packedBoxes->getSerializedPackedBoxList() as $packedBox) {
                    $package = new RatePackage();
                    $package->setPounds($packedBox['weight']);
                    $package->setOunces(0);
                    $package->setField('Machinable', 'True');
                    $package->setField('MailType', 'Package');
                    $package->setField('ValueOfContents', $order->getTotalSaleAmount());
                    $package->setField('Country', $countryName);

                    $package->setField('Container', RatePackage::CONTAINER_RECTANGULAR);

                    // Mismatched on purpose!
                    $package->setField('Width', (int)$packedBox['height']);
                    $package->setField('Length', (int)$packedBox['width']);
                    $package->setField('Height', (int)$packedBox['length']);

                    $package->setField('OriginZip', $this->_parseZipCode($storeLocation->postalCode));
                    $package->setField('CommercialFlag', 'N');

                    if ($order->shippingAddress->postalCode) {
                        $package->setField('AcceptanceDateTime', DateTimeHelper::toIso8601(time()));
                        $package->setField('DestinationPostalCode', $this->_parseZipCode($order->shippingAddress->postalCode));
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
            if (self::isDomestic($countryCode)) {
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
                    $serviceName = $service['MailService'] ?? null;

                    // ID for international, CLASSID for domestic
                    $serviceHandleKey = $service['@attributes']['CLASSID'] ?? $service['@attributes']['ID'] ?? null;
                    $serviceHandle = $this->_getServiceHandle($serviceHandleKey, $serviceName, $countryCode);

                    // The API returns rates for each package in the request, so combine them before reporting back
                    // Postage for international, Rate for domestic
                    $amount = $service['Postage'] ?? $service['Rate'] ?? 0;

                    if (isset($this->_rates[$serviceHandle]['amount'])) {
                        $amount = $this->_rates[$serviceHandle]['amount'] + $amount;
                    }

                    // Combine the package and service information as options
                    $optionData = array_merge($package, $service);
                    unset($optionData['Service'], $optionData['Postage']);

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
            $sender = TestingHelper::getTestAddress('US', ['locality' => 'Cupertino']);
            $recipient = TestingHelper::getTestAddress('US', ['locality' => 'Mountain View']);

            // Create a test package
            $packedBoxes = TestingHelper::getTestPackedBoxes($this->dimensionUnit, $this->weightUnit);
            $packedBox = $packedBoxes[0];

            // Create a test payload
            $client = $this->_getClient();

            $package = new RatePackage();
            $package->setService(RatePackage::SERVICE_ALL);
            $package->setFirstClassMailType(RatePackage::MAIL_TYPE_PARCEL);
            $package->setZipOrigination($sender->postalCode);
            $package->setZipDestination($recipient->postalCode);
            $package->setPounds($packedBox['weight']);
            $package->setOunces(0);
            $package->setContainer('');
            $package->setSize(RatePackage::SIZE_REGULAR);
            $package->setField('Machinable', true);
            $client->addPackage($package);

            $client->getRate();
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

    private function _getClient(): ?Rate
    {
        if (!$this->_client) {
            $username = $this->getSetting('username');

            $this->_client = new Rate($username);
        }

        return $this->_client;
    }

    private function _getServiceHandle($code, $name, $countryCode)
    {
        if (self::isDomestic($countryCode)) {
            // Some First-Class rates are all 0, which is ambiguous, so use the name
            if ($code == '0') {
                if ($name === 'First-Class Mail Large Envelope') {
                    $code = '0A';
                } else if ($name === 'First-Class Mail Letter') {
                    $code = '0B';
                } else if ($name === 'First-Class Mail Parcel') {
                    $code = '0C';
                } else if ($name === 'First-Class Mail Postcards') {
                    $code = '0D';
                } else {
                    $code = '61';
                }
            }

            $services = [
                'PRIORITY_MAIL_EXPRESS' => '3',
                'PRIORITY_MAIL_EXPRESS_SUNDAY_HOLIDAY_DELIVERY' => '23',
                'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE' => '13',
                'PRIORITY_MAIL_EXPRESS_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '25',
                'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE' => '30',
                'PRIORITY_MAIL_EXPRESS_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '32',
                'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE' => '62',
                'PRIORITY_MAIL_EXPRESS_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => '64',
                'PRIORITY_MAIL_EXPRESS_FLAT_RATE_BOXES' => '55',
                'PRIORITY_MAIL_EXPRESS_SUNDAY_HOLIDAY_DELIVERY_FLAT_RATE_BOXES' => '57',

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
                'PRIORITY_MAIL_REGIONAL_RATE_BOX_A' => '47',
                'PRIORITY_MAIL_REGIONAL_RATE_BOX_B' => '49',
                'PRIORITY_MAIL_REGIONAL_RATE_BOX_C' => '58',

                'FIRST_CLASS_MAIL_LARGE_ENVELOPE' => '0A',
                'FIRST_CLASS_MAIL_LETTER' => '0B',
                'FIRST_CLASS_MAIL_PARCEL' => '0C',
                'FIRST_CLASS_MAIL_POSTCARDS' => '0D',
                'FIRST_CLASS_MAIL_LARGE_POSTCARDS' => '15',
                'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => '61',

                'GROUND_ADVANTAGE' => '1058',
                'GROUND_ADVANTAGE_CUBIC' => '1096',
                'GROUND_ADVANTAGE_HOLD_FOR_PICKUP' => '2058',
                'GROUND_ADVANTAGE_CUBIC_HOLD_FOR_PICKUP' => '2096',
                'GROUND_ADVANTAGE_HAZMAT' => '4058',
                'GROUND_ADVANTAGE_CUBIC_HAZMAT' => '4096',
                'GROUND_ADVANTAGE_PARCEL_LOCKER' => '6058',
                'GROUND_ADVANTAGE_CUBIC_PARCEL_LOCKER' => '6096',

                'STANDARD_PARCEL_POST' => '4',
                'MEDIA_MAIL_PARCEL' => '6',
                'LIBRARY_MAIL_PARCEL' => '7',
            ];
        } else {
            $services = [
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => '1',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_FLAT_RATE_ENVELOPE' => '10',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_LEGAL_FLAT_RATE_ENVELOPE' => '17',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_FLAT_RATE_BOXES' => '26',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL_PADDED_FLAT_RATE_ENVELOPE' => '27',

                'PRIORITY_MAIL_INTERNATIONAL' => '2',
                'PRIORITY_MAIL_INTERNATIONAL_FLAT_RATE_ENVELOPE' => '8',
                'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => '17',
                'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX' => '18',
                'PRIORITY_MAIL_INTERNATIONAL_SMALL_FLAT_RATE_BOX' => '16',
                'PRIORITY_MAIL_INTERNATIONAL_GIFT_CARD_FLAT_RATE_ENVELOPE' => '18',
                'PRIORITY_MAIL_INTERNATIONAL_WINDOW_FLAT_RATE_ENVELOPE' => '19',
                'PRIORITY_MAIL_INTERNATIONAL_SMALL_FLAT_RATE_ENVELOPE' => '20',
                'PRIORITY_MAIL_INTERNATIONAL_LEGAL_FLAT_RATE_ENVELOPE' => '22',
                'PRIORITY_MAIL_INTERNATIONAL_PADDED_FLAT_RATE_ENVELOPE' => '23',
                'PRIORITY_MAIL_INTERNATIONAL_DVD_FLAT_RATE_BOX' => '24',
                'PRIORITY_MAIL_INTERNATIONAL_LARGE_VIDEO_FLAT_RATE_BOX' => '25',

                'FIRST_CLASS_MAIL_INTERNATIONAL' => '14',
                'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => '15',
                'FIRST_CLASS_MAIL_INTERNATIONAL_LETTER' => '13',
                'FIRST_CLASS_MAIL_INTERNATIONAL_POSTCARD' => '21',

                'USPS_GLOBAL_EXPRESS_GUARANTEED' => '4',
                'USPS_GLOBAL_EXPRESS_GUARANTEED_DOCUMENT' => '5',
                'USPS_GLOBAL_EXPRESS_GUARANTEED_NON_DOCUMENT_RECTANGULAR' => '6',
                'USPS_GLOBAL_EXPRESS_GUARANTEED_NON_DOCUMENT_NON_RECTANGULAR' => '7',
                'USPS_GXG_ENVELOPES' => '12',
            ];
        }

        return array_search($code, $services);
    }

    private function _parseZipCode($zip): string
    {
        return explode('-', $zip)[0] ?? $zip;
    }
}
