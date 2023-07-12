<?php
namespace verbb\postie\inc\royalmail;

use verbb\postie\helpers\PostieHelper;

use Craft;
use craft\helpers\StringHelper;

use craft\commerce\elements\Order;

class RoyalMailRates
{
    // Properties
    // =========================================================================

    public static ?Order $order = null;
    public static bool $checkCompensation = false;
    public static bool $includeVat = false;

    private static array $euro = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

    private static $europeZone1 = ['IE', 'DE', 'DK', 'FR', 'MC'];
    private static $europeZone2 = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'EE', 'FI', 'GR', 'HU', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
    private static $europeZone3 = ['AL', 'AD', 'AM', 'AZ', 'BY', 'BA', 'FO', 'GE', 'GI', 'GL', 'IS', 'KZ', 'KG', 'LI', 'MD', 'ME', 'MK', 'NO', 'RU', 'SM', 'RS', 'CH', 'TJ', 'TR', 'TM', 'UA', 'UZ', 'VA'];

    private static array $worldZone2 = ['AU', 'PW','IO', 'CX', 'CC', 'CK', 'FJ', 'PF', 'TF', 'KI', 'MO', 'NR', 'NC', 'NZ', 'PG', 'NU', 'NF', 'LA', 'PN', 'TO', 'TV', 'WS', 'AS', 'SG', 'SB', 'TK'];

    private static array $worldZone3 = ['US'];

    private static array $farEast = ['CN', 'HK', 'MO', 'JP', 'MN', 'KP', 'KR', 'TW', 'BN', 'KH', 'TL', 'ID', 'LA', 'MY', 'MM', 'PH', 'SG', 'TH', 'VN', 'RU'];

    private static array $australasia = ['AU', 'PF', 'NU', 'TO', 'CX', 'KI', 'PG', 'TV', 'CC', 'NR', 'PN', 'VU', 'CK', 'NC', 'SB', 'WF', 'FJ', 'NZ', 'TK', 'WS'];

    private static array $europeNonEu = ['AL', 'AD', 'AM', 'AZ', 'BY', 'BA', 'GE', 'IS', 'LI', 'MD', 'MC', 'ME', 'MK', 'NO', 'RU', 'SM', 'RS', 'CH', 'TR', 'UA', 'GB', 'VA',];

    protected static array $rateYears = [
        '2019' => '2019-03-25',
        '2020' => '2020-03-23',
        '2021' => '2021-01-01',
        '2022' => '2022-04-04',
    ];

    protected static array $internationalDefaultBox = [
        'letter' => [
            'length' => 240,
            'width' => 165,
            'height' => 5,
            'weight' => 100,
        ],
        'large-letter' => [
            'length' => 353,
            'width' => 250,
            'height' => 25,
            'weight' => 750,
        ],
        'long-parcel' => [
            'length' => 600,
            'width' => 150,
            'height' => 150,
            'weight' => 2000,
        ],
        'square-parcel' => [
            'length' => 300,
            'width' => 300,
            'height' => 300,
            'weight' => 2000,
        ],
        'parcel' => [
            'length' => 450,
            'width' => 225,
            'height' => 225,
            'weight' => 2000,
        ],
    ];


    // Public Methods
    // =========================================================================

    public static function getRates($country, $service, $provider, $order): array
    {
        $rates = [];

        // Set some variables
        self::$checkCompensation = $provider->getSetting('checkCompensation');
        self::$includeVat = $provider->getSetting('includeVat');
        self::$order = $order;

        // Get the function we should be using rates for
        $methodName = 'get' . StringHelper::toPascalCase($service) . 'Rates';

        // Find the rates for the destination country
        if (method_exists(self::class, $methodName)) {
            $rates = self::$methodName($country);

            if ($rates) {
                foreach ($rates as $key => &$rate) {
                    $price = $rate['price'] ?? null;

                    // Only return a box if it contains a price for zone
                    if (!$price) {
                        unset($rates[$key]);
                    } else {
                        // All pricing in pence
                        $rate['price'] = $rate['price'] / 100;
                    }
                }
            }
        }

        return $rates;
    }


    // Protected Methods
    // =========================================================================

    protected static function getFirstClassRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    100 => 85,
                ],
                'large-letter' => [
                    100 => 129,
                    250 => 183,
                    500 => 239,
                    750 => 330,
                ],
                'small-parcel-wide' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'small-parcel-deep' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'small-parcel-bigger' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'medium-parcel' => [
                    1000 => 600,
                    2000 => 902,
                    5000 => 1585,
                    10000 => 2190,
                    20000 => 3340,
                ],
            ],

            '2022' => [
                'letter' => [
                    100 => 95,
                ],
                'large-letter' => [
                    100 => 145,
                    250 => 205,
                    500 => 265,
                    750 => 330,
                ],
                'small-parcel-wide' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'small-parcel-deep' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'small-parcel-bigger' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'medium-parcel' => [
                    1000 => 695,
                    2000 => 695,
                    5000 => 795,
                    10000 => 795,
                    20000 => 1295,
                ],
            ],
        ];

        $boxes = [
            'letter' => [
                'length' => 240,
                'width' => 165,
                'height' => 5,
                'weight' => 100,
            ],
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands, 20);
    }

    protected static function getFirstClassSignedRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    100 => 85,
                ],
                'large-letter' => [
                    100 => 129,
                    250 => 183,
                    500 => 239,
                    750 => 330,
                ],
                'small-parcel-wide' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'small-parcel-deep' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'small-parcel-bigger' => [
                    1000 => 385,
                    2000 => 557,
                ],
                'medium-parcel' => [
                    1000 => 600,
                    2000 => 902,
                    5000 => 1585,
                    10000 => 2190,
                    20000 => 3340,
                ],
            ],

            '2022' => [
                'letter' => [
                    100 => 95,
                ],
                'large-letter' => [
                    100 => 145,
                    250 => 205,
                    500 => 265,
                    750 => 330,
                ],
                'small-parcel-wide' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'small-parcel-deep' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'small-parcel-bigger' => [
                    1000 => 445,
                    2000 => 445,
                ],
                'medium-parcel' => [
                    1000 => 695,
                    2000 => 695,
                    5000 => 795,
                    10000 => 795,
                    20000 => 1295,
                ],
            ],
        ];

        $boxes = [
            'letter' => [
                'length' => 240,
                'width' => 165,
                'height' => 5,
                'weight' => 100,
            ],
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands, 50);

        $signedForCost = self::getValueForYear([
            '2019' => 120,
            '2020' => 130,
            '2021' => 140,
        ]);

        $signedForPackageCost = self::getValueForYear([
            '2019' => 100,
            '2022' => 110,
        ]);

        foreach ($boxPricing as $key => &$box) {
            if (str_contains($key, 'letter-')) {
                $additionalCost = $signedForCost;
            } else {
                $additionalCost = $signedForPackageCost;
            }

            if ($additionalCost) {
                $box['price'] += $additionalCost;
            }
        }

        return $boxPricing;
    }

    protected static function getSecondClassRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    100 => 66,
                ],
                'large-letter' => [
                    100 => 96,
                    250 => 153,
                    500 => 199,
                    750 => 270,
                ],
                'small-parcel-wide' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'small-parcel-deep' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'small-parcel-bigger' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'medium-parcel' => [
                    1000 => 530,
                    2000 => 530,
                    5000 => 899,
                    10000 => 2025,
                    20000 => 2855,
                ],
            ],

            '2022' => [
                'letter' => [
                    100 => 68,
                ],
                'large-letter' => [
                    100 => 105,
                    250 => 165,
                    500 => 215,
                    750 => 270,
                ],
                'small-parcel-wide' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'small-parcel-deep' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'small-parcel-bigger' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'medium-parcel' => [
                    1000 => 535,
                    2000 => 535,
                    5000 => 695,
                    10000 => 695,
                    20000 => 1045,
                ],
            ],
        ];

        $boxes = [
            'letter' => [
                'length' => 240,
                'width' => 165,
                'height' => 5,
                'weight' => 100,
            ],
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands, 20);
    }

    protected static function getSecondClassSignedRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    100 => 66,
                ],
                'large-letter' => [
                    100 => 96,
                    250 => 153,
                    500 => 199,
                    750 => 270,
                ],
                'small-parcel-wide' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'small-parcel-deep' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'small-parcel-bigger' => [
                    1000 => 320,
                    2000 => 320,
                ],
                'medium-parcel' => [
                    1000 => 530,
                    2000 => 530,
                    5000 => 899,
                    10000 => 2025,
                    20000 => 2855,
                ],
            ],

            '2022' => [
                'letter' => [
                    100 => 68,
                ],
                'large-letter' => [
                    100 => 105,
                    250 => 165,
                    500 => 215,
                    750 => 270,
                ],
                'small-parcel-wide' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'small-parcel-deep' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'small-parcel-bigger' => [
                    1000 => 335,
                    2000 => 335,
                ],
                'medium-parcel' => [
                    1000 => 535,
                    2000 => 535,
                    5000 => 695,
                    10000 => 695,
                    20000 => 1045,
                ],
            ],
        ];

        $boxes = [
            'letter' => [
                'length' => 240,
                'width' => 165,
                'height' => 5,
                'weight' => 100,
            ],
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands, 50);

        $signedForCost = self::getValueForYear([
            '2019' => 120,
            '2020' => 130,
            '2021' => 140,
        ]);

        $signedForPackageCost = self::getValueForYear([
            '2019' => 100,
            '2022' => 110,
        ]);

        foreach ($boxPricing as $key => &$box) {
            if (str_contains($key, 'letter-')) {
                $additionalCost = $signedForCost;
            } else {
                $additionalCost = $signedForPackageCost;
            }

            if ($additionalCost) {
                $box['price'] += $additionalCost;
            }
        }

        return $boxPricing;
    }

    protected static function getSpecialDelivery9amRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $excluded = [
            'GG', // Guernsey
            'IM', // Isle of Man
            'JE', // Jersey
        ];

        if (in_array($country, $excluded)) {
            return [];
        }

        $bands = [
            '2021' => [
                'packet-50' => [
                    100 => 2226,
                    500 => 2518,
                    1000 => 2729,
                    2000 => 3119,
                ],
                'packet-1000' => [
                    100 => 2446,
                    500 => 2738,
                    1000 => 2949,
                    2000 => 3339,
                ],
                'packet-more' => [
                    100 => 2796,
                    500 => 3088,
                    1000 => 3299,
                    2000 => 3689,
                ],
            ],

            '2022' => [
                'packet-50' => [
                    100 => 2295,
                    500 => 2595,
                    1000 => 2795,
                    2000 => 3395,
                ],
                'packet-1000' => [
                    100 => 2515,
                    500 => 2815,
                    1000 => 3015,
                    2000 => 3615,
                ],
                'packet-more' => [
                    100 => 2865,
                    500 => 3165,
                    1000 => 3365,
                    2000 => 3965,
                ],
            ],
        ];

        $boxes = [
            'packet-50' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 2000,
            ],
            'packet-1000' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 2000,
            ],
            'packet-more' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 2000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getSpecialDelivery1pmRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'packet-500' => [
                    100 => 685,
                    500 => 765,
                    1000 => 895,
                    2000 => 1115,
                    10000 => 2675,
                    20000 => 4135,
                ],
                'packet-1000' => [
                    100 => 785,
                    500 => 865,
                    1000 => 995,
                    2000 => 1215,
                    10000 => 2775,
                    20000 => 4235,
                ],
                'packet-2500' => [
                    100 => 985,
                    500 => 1065,
                    1000 => 1195,
                    2000 => 1415,
                    10000 => 2975,
                    20000 => 4435,
                ],
            ],

            '2022' => [
                'packet-500' => [
                    100 => 685,
                    500 => 765,
                    1000 => 895,
                    2000 => 1115,
                    10000 => 1545,
                    20000 => 1945,
                ],
                'packet-1000' => [
                    100 => 785,
                    500 => 865,
                    1000 => 995,
                    2000 => 1215,
                    10000 => 1645,
                    20000 => 2045,
                ],
                'packet-2500' => [
                    100 => 985,
                    500 => 1065,
                    1000 => 1195,
                    2000 => 1415,
                    10000 => 1845,
                    20000 => 2245,
                ],
            ],
        ];

        $boxes = [
            'packet-500' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
                'itemValue' => 500,
            ],
            'packet-1000' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
                'itemValue' => 1000,
            ],
            'packet-2500' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
                'itemValue' => 2500,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    protected static function getParcelforceExpress9Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2018' => [
                'packet-200' => [
                    2000 => 3990,
                    5000 => 4092,
                    10000 => 4434,
                    15000 => 5118,
                    20000 => 5658,
                    25000 => 6780,
                    30000 => 7200,
                ],
            ],

            '2022' => [
                'packet-200' => [
                    2000 => 3990,
                    5000 => 3990,
                    10000 => 4443,
                    15000 => 5118,
                    20000 => 5118,
                    25000 => 6780,
                    30000 => 6780,
                ],
            ],
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
                'itemValue' => 500,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getParcelforceExpress10Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2018' => [
                'packet-200' => [
                    2000 => 2982,
                    5000 => 3084,
                    10000 => 3426,
                    15000 => 4104,
                    20000 => 4644,
                    25000 => 5772,
                    30000 => 6192,
                ],
            ],

            '2022' => [
                'packet-200' => [
                    2000 => 2200,
                    5000 => 2200,
                    10000 => 2300,
                    15000 => 2500,
                    20000 => 2500,
                    25000 => 2800,
                    30000 => 2800,
                ],
            ],
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getParcelforceExpressAmRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2018' => [
                'packet-200' => [
                    2000 => 1974,
                    5000 => 2070,
                    10000 => 2418,
                    15000 => 3096,
                    20000 => 3642,
                    25000 => 4764,
                    30000 => 5184,
                ],
            ],

            '2022' => [
                'packet-200' => [
                    2000 => 1849,
                    5000 => 1849,
                    10000 => 1969,
                    15000 => 2149,
                    20000 => 2149,
                    25000 => 2449,
                    30000 => 2449,
                ],
            ],
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getParcelforceExpress24Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2018' => [
                'packet-100' => [
                    2000 => 1668,
                    5000 => 1770,
                    10000 => 2112,
                    15000 => 2796,
                    20000 => 3336,
                    25000 => 4458,
                    30000 => 4878,
                ],
            ],

            '2022' => [
                'packet-100' => [
                    2000 => 935,
                    5000 => 935,
                    10000 => 1335,
                    15000 => 1535,
                    20000 => 1535,
                    25000 => 1835,
                    30000 => 1835,
                ],
            ],
        ];

        $boxes = [
            'packet-100' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getParcelforceExpress48Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2018' => [
                'packet-100' => [
                    2000 => 1212,
                    5000 => 1314,
                    10000 => 1662,
                    15000 => 2340,
                    20000 => 2880,
                    25000 => 4008,
                    30000 => 4422,
                ],
            ],

            '2022' => [
                'packet-100' => [
                    2000 => 895,
                    5000 => 895,
                    10000 => 995,
                    15000 => 1195,
                    20000 => 1195,
                    25000 => 1495,
                    30000 => 1495,
                ],
            ],
        ];

        $boxes = [
            'packet-100' => [
                'length' => 2500,
                'width' => 1250,
                'height' => 1250,
                'weight' => 30000,
            ],
        ];

        $boxPricing = self::getBoxPricing($boxes, $bands);

        foreach ($boxPricing as $key => $box) {
            // 20% VAT
            if (!self::$includeVat) {
                $boxPricing[$key]['price'] = $box['price'] / 1.2;
            }
        }

        return $boxPricing;
    }

    protected static function getTracked24Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'large-letter' => [
                    750 => 402,
                ],
                'small-parcel-wide' => [
                    1000 => 546,
                    2000 => 780,
                ],
                'small-parcel-deep' => [
                    1000 => 546,
                    2000 => 780,
                ],
                'small-parcel-bigger' => [
                    1000 => 546,
                    2000 => 780,
                ],
                'medium-parcel' => [
                    1000 => 846,
                    2000 => 1194,
                    5000 => 1800,
                    10000 => 2160,
                    20000 => 3360,
                ],
            ],

            '2022' => [
                'large-letter' => [
                    750 => 380,
                ],
                'small-parcel-wide' => [
                    1000 => 545,
                    2000 => 545,
                ],
                'small-parcel-deep' => [
                    1000 => 545,
                    2000 => 545,
                ],
                'small-parcel-bigger' => [
                    1000 => 545,
                    2000 => 545,
                ],
                'medium-parcel' => [
                    1000 => 795,
                    2000 => 795,
                    5000 => 895,
                    10000 => 895,
                    20000 => 1445,
                ],
            ],
        ];

        $boxes = [
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands, 100);
    }

    protected static function getTracked48Rates($country): array
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'large-letter' => [
                    750 => 360,
                ],
                'small-parcel-wide' => [
                    1000 => 474,
                    2000 => 474,
                ],
                'small-parcel-deep' => [
                    1000 => 474,
                    2000 => 474,
                ],
                'small-parcel-bigger' => [
                    1000 => 474,
                    2000 => 474,
                ],
                'medium-parcel' => [
                    1000 => 726,
                    2000 => 726,
                    5000 => 1055,
                    10000 => 1680,
                    20000 => 3000,
                ],
            ],

            '2022' => [
                'large-letter' => [
                    750 => 320,
                ],
                'small-parcel-wide' => [
                    1000 => 435,
                    2000 => 435,
                ],
                'small-parcel-deep' => [
                    1000 => 435,
                    2000 => 435,
                ],
                'small-parcel-bigger' => [
                    1000 => 435,
                    2000 => 435,
                ],
                'medium-parcel' => [
                    1000 => 635,
                    2000 => 635,
                    5000 => 795,
                    10000 => 795,
                    20000 => 1145,
                ],
            ],
        ];

        $boxes = [
            'large-letter' => [
                'length' => 353,
                'width' => 250,
                'height' => 25,
                'weight' => 750,
            ],
            'small-parcel-wide' => [
                'length' => 450,
                'width' => 350,
                'height' => 80,
                'weight' => 2000,
            ],
            'small-parcel-deep' => [
                'length' => 350,
                'width' => 250,
                'height' => 160,
                'weight' => 2000,
            ],
            'small-parcel-bigger' => [
                'length' => 450,
                'width' => 350,
                'height' => 160,
                'weight' => 2000,
            ],
            'medium-parcel' => [
                'length' => 610,
                'width' => 460,
                'height' => 460,
                'weight' => 20000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands, 100);
    }


    protected static function getInternationalStandardRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    10 => [170, 170, 170, 170, 170, 170],
                    20 => [170, 170, 170, 170, 170, 170],
                    100 => [170, 170, 170, 255, 255, 255],
                ],
                'large-letter' => [
                    100 => [325, 325, 325, 420, 420, 420],
                    250 => [425, 425, 425, 570, 680, 585],
                    500 => [525, 525, 525, 800, 985, 830],
                    750 => [625, 625, 625, 1065, 1355, 1110],
                ],
                'packet' => [
                    100 => [580, 580, 580, 715, 835, 935],
                    250 => [595, 595, 595, 830, 990, 1095],
                    500 => [780, 780, 780, 1210, 1450, 1660],
                    750 => [905, 905, 905, 1485, 1760, 1935],
                    1000 => [1020, 1020, 1020, 1765, 2085, 2300],
                    1250 => [1105, 1105, 1105, 1985, 2375, 2645],
                    1500 => [1210, 1210, 1210, 2210, 2685, 2920],
                    2000 => [1300, 1300, 1300, 2330, 2855, 3045],
                ],
                'printed-papers' => [
                    100 => [580, 580, 580, 715, 835, 935],
                    250 => [595, 595, 595, 830, 990, 1095],
                    500 => [780, 780, 780, 1210, 1450, 1660],
                    750 => [905, 905, 905, 1485, 1760, 1935],
                    1000 => [1020, 1020, 1020, 1765, 2085, 2300],
                    1250 => [1105, 1105, 1105, 1985, 2375, 2645],
                    1500 => [1210, 1210, 1210, 2210, 2685, 2920],
                    2000 => [1300, 1300, 1300, 2330, 2855, 3045],
                    2250 => [1440, 1440, 1440, 2520, 3090, 3305],
                    2500 => [1580, 1580, 1580, 2710, 3325, 3565],
                    2750 => [1720, 1720, 1720, 2900, 3560, 3825],
                    3000 => [1860, 1860, 1860, 3090, 3795, 4085],
                    3250 => [2000, 2000, 2000, 3280, 4030, 4345],
                    3500 => [2140, 2140, 2140, 3470, 4265, 4605],
                    3750 => [2280, 2280, 2280, 3660, 4500, 4865],
                    4000 => [2420, 2420, 2420, 3850, 4735, 5125],
                    4250 => [2560, 2560, 2560, 4040, 4970, 5385],
                    4500 => [2700, 2700, 2700, 4230, 5205, 5645],
                    4750 => [2840, 2840, 2840, 4420, 5440, 5905],
                    5000 => [2980, 2980, 2980, 4610, 5675, 6165],
                ],
            ],

            '2022' => [
                'letter' => [
                    10 => [185, 185, 185, 185, 185, 185],
                    20 => [185, 185, 185, 185, 185, 185],
                    100 => [185, 185, 185, 255, 255, 255],
                ],
                'large-letter' => [
                    100 => [325, 325, 325, 420, 420, 420],
                    250 => [425, 425, 425, 570, 680, 585],
                    500 => [525, 525, 525, 800, 985, 830],
                    750 => [625, 625, 625, 1065, 1355, 1110],
                ],
                'packet' => [
                    100 => [585, 595, 630, 715, 835, 935],
                    250 => [585, 595, 630, 830, 990, 1095],
                    500 => [750, 780, 820, 1210, 1450, 1660],
                    750 => [870, 905, 955, 1485, 1760, 1935],
                    1000 => [990, 1020, 1090, 1765, 2085, 2300],
                    1250 => [1090, 1140, 1195, 1985, 2375, 2645],
                    1500 => [1090, 1140, 1305, 2210, 2685, 2920],
                    2000 => [1240, 1290, 1405, 2330, 2855, 3045],
                ],
                'printed-papers' => [
                    100 => [585, 595, 630, 715, 835, 935],
                    250 => [585, 595, 630, 830, 990, 1095],
                    500 => [750, 780, 820, 1210, 1450, 1660],
                    750 => [870, 905, 955, 1485, 1760, 1935],
                    1000 => [990, 1020, 1090, 1765, 2085, 2300],
                    1250 => [1090, 1140, 1195, 1985, 2375, 2645],
                    1500 => [1090, 1140, 1305, 2210, 2685, 2920],
                    2000 => [1240, 1290, 1405, 2330, 2855, 3045],
                    2250 => [1380, 1430, 1545, 2520, 3090, 3305],
                    2500 => [1520, 1570, 1685, 2710, 3325, 3565],
                    2750 => [1660, 1710, 1825, 2900, 3560, 3825],
                    3000 => [1800, 1850, 1965, 3090, 3795, 4085],
                    3250 => [1940, 1990, 2105, 3280, 4030, 4345],
                    3500 => [2080, 2130, 2245, 3470, 4265, 4605],
                    3750 => [2220, 2270, 2385, 3660, 4500, 4865],
                    4000 => [2360, 2410, 2525, 3850, 4735, 5125],
                    4250 => [2500, 2550, 2665, 4040, 4970, 5385],
                    4500 => [2640, 2690, 2805, 4230, 5205, 5645],
                    4750 => [2780, 2830, 2945, 4420, 5440, 5905],
                    5000 => [2920, 2970, 3085, 4610, 5675, 6165],
                ],
            ],
        ];

        return self::getInternationalBoxPricing($bands, $country, 20);
    }

    protected static function getInternationalTrackedSignedRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = ['AX', 'AD', 'AR', 'AT', 'BB', 'BY', 'BE', 'BZ', 'BG', 'KH', 'CA', 'KY', 'CK', 'HR', 'CY', 'CZ', 'DK', 'EC', 'FO', 'FI', 'FR', 'GE', 'DE', 'GI', 'GR', 'GL', 'HK', 'HU', 'IS', 'ID', 'IE', 'IT', 'JP', 'LV', 'LB', 'LI', 'LT', 'LU', 'MY', 'MT', 'MD', 'NL', 'NZ', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS', 'SG', 'SK', 'SI', 'KR', 'ES', 'SE', 'CH', 'TH', 'TO', 'TT', 'TR', 'UG', 'AE', 'US', 'VA'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    10 => [675, 675, 675, 675, 675, 675],
                    20 => [675, 675, 675, 675, 675, 675],
                    100 => [675, 675, 675, 775, 775, 775],
                ],
                'large-letter' => [
                    100 => [865, 865, 865, 960, 965, 965],
                    250 => [885, 885, 885, 1035, 1145, 1055],
                    500 => [985, 985, 985, 1220, 1400, 1250],
                    750 => [1025, 1025, 1025, 1405, 1680, 1450],
                ],
                'packet' => [
                    100 => [1010, 1010, 1010, 1175, 1280, 1185],
                    250 => [1025, 1025, 1025, 1270, 1415, 1345],
                    500 => [1170, 1170, 1170, 1635, 1855, 1810],
                    750 => [1275, 1275, 1275, 1875, 2135, 2085],
                    1000 => [1370, 1370, 1370, 2140, 2450, 2450],
                    1250 => [1410, 1410, 1410, 2325, 2690, 2795],
                    1500 => [1485, 1485, 1485, 2465, 2940, 3070],
                    2000 => [1525, 1525, 1525, 2565, 3100, 3195],
                ],
                'printed-papers' => [
                    100 => [1010, 1010, 1010, 1175, 1280, 1185],
                    250 => [1025, 1025, 1025, 1270, 1415, 1345],
                    500 => [1170, 1170, 1170, 1635, 1855, 1810],
                    750 => [1275, 1275, 1275, 1875, 2135, 2085],
                    1000 => [1370, 1370, 1370, 2140, 2450, 2450],
                    1250 => [1410, 1410, 1410, 2325, 2690, 2795],
                    1500 => [1485, 1485, 1485, 2465, 2940, 3070],
                    2000 => [1525, 1525, 1525, 2565, 3100, 3195],
                    2250 => [1665, 1665, 1665, 2755, 3335, 3455],
                    2500 => [1805, 1805, 1805, 2945, 3570, 3715],
                    2750 => [1945, 1945, 1945, 3135, 3805, 3975],
                    3000 => [2085, 2085, 2085, 3325, 4040, 4235],
                    3250 => [2225, 2225, 2225, 3515, 4275, 4495],
                    3500 => [2365, 2365, 2365, 3705, 4510, 4755],
                    3750 => [2505, 2505, 2505, 3895, 4745, 5015],
                    4000 => [2645, 2645, 2645, 4085, 4980, 5275],
                    4250 => [2785, 2785, 2785, 4275, 5215, 5535],
                    4500 => [2925, 2925, 2925, 4465, 5450, 5795],
                    4750 => [3065, 3065, 3065, 4655, 5685, 6055],
                    5000 => [3205, 3205, 3205, 4845, 5920, 6315],
                ],
            ],
            '2022' => [
                'letter' => [
                    10 => [690, 690, 690, 690, 690, 690],
                    20 => [690, 690, 690, 690, 690, 690],
                    100 => [690, 690, 690, 775, 775, 775],
                ],
                'large-letter' => [
                    100 => [865, 865, 865, 960, 965, 965],
                    250 => [885, 885, 885, 1035, 1145, 1055],
                    500 => [985, 985, 985, 1220, 1400, 1250],
                    750 => [1025, 1025, 1025, 1405, 1680, 1450],
                ],
                'packet' => [
                    100 => [990, 995, 1020, 1125, 1230, 1215],
                    250 => [990, 995, 1020, 1220, 1365, 1345],
                    500 => [1115, 1120, 1160, 1585, 1805, 1810],
                    750 => [1215, 1225, 1275, 1825, 2085, 2085],
                    1000 => [1310, 1320, 1390, 2090, 2400, 2440],
                    1250 => [1400, 1400, 1450, 2275, 2640, 2760],
                    1500 => [1400, 1400, 1530, 2415, 2890, 2760],
                    2000 => [1400, 1465, 1580, 2515, 3050, 2760],
                ],
                'printed-papers' => [
                    100 => [990, 995, 1020, 1125, 1230, 1215],
                    250 => [990, 995, 1020, 1220, 1365, 1345],
                    500 => [1115, 1120, 1160, 1585, 1805, 1810],
                    750 => [1215, 1225, 1275, 1825, 2085, 2085],
                    1000 => [1310, 1320, 1390, 2090, 2400, 2440],
                    1250 => [1400, 1400, 1450, 2275, 2640, 2760],
                    1500 => [1400, 1400, 1530, 2415, 2890, 2760],
                    2000 => [1400, 1465, 1580, 2515, 3050, 2760],
                    2250 => [1540, 1605, 1720, 2705, 3285, 3020],
                    2500 => [1680, 1745, 1860, 2895, 3520, 3280],
                    2750 => [1820, 1885, 2000, 3085, 3755, 3540],
                    3000 => [1960, 2025, 2140, 3275, 3990, 3800],
                    3250 => [2100, 2165, 2280, 3465, 4225, 4060],
                    3500 => [2240, 2305, 2420, 3655, 4460, 4320],
                    3750 => [2380, 2445, 2560, 3845, 4695, 4580],
                    4000 => [2520, 2585, 2700, 4035, 4930, 4840],
                    4250 => [2660, 2725, 2840, 4225, 5165, 5100],
                    4500 => [2800, 2865, 2980, 4415, 5400, 5360],
                    4750 => [2940, 3005, 3120, 4605, 5635, 5620],
                    5000 => [3080, 3145, 3260, 4795, 5870, 5880],
                ],
            ],
        ];

        return self::getInternationalBoxPricing($bands, $country);
    }

    protected static function getInternationalTrackedRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = ['AX', 'AD', 'AU', 'AT', 'BE', 'BR', 'CA', 'HR', 'CY', 'DK', 'EE', 'FO', 'FI', 'FR', 'DE', 'GI', 'GR', 'GL', 'HK', 'HU', 'IS', 'IN', 'IE', 'IL', 'IT', 'LV', 'LB', 'LI', 'LT', 'LU', 'MY', 'MT', 'NL', 'NZ', 'NO', 'PL', 'PT', 'RU', 'SM', 'RS', 'SG', 'SK', 'SI', 'KR', 'ES', 'SE', 'CH', 'TR', 'US', 'VA'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    10 => [675, 675, 675, 675],
                    20 => [675, 675, 675, 675],
                    100 => [675, 775, 775, 775],
                ],
                'large-letter' => [
                    100 => [865, 960, 965, 965],
                    250 => [885, 1035, 1145, 1055],
                    500 => [985, 1220, 1400, 1250],
                    750 => [1025, 1405, 1680, 1450],
                ],
                'packet' => [
                    100 => [1010, 1175, 1280, 1185],
                    250 => [1025, 1270, 1415, 1345],
                    500 => [1170, 1635, 1855, 1810],
                    750 => [1275, 1875, 2135, 2085],
                    1000 => [1370, 2140, 2450, 2450],
                    1250 => [1410, 2325, 2690, 2795],
                    1500 => [1485, 2465, 2940, 3070],
                    2000 => [1525, 2565, 3100, 3195],
                ],
                'printed-papers' => [
                    100 => [1010, 1175, 1280, 1185],
                    250 => [1025, 1270, 1415, 1345],
                    500 => [1170, 1635, 1855, 1810],
                    750 => [1275, 1875, 2135, 2085],
                    1000 => [1370, 2140, 2450, 2450],
                    1250 => [1410, 2325, 2690, 2795],
                    1500 => [1485, 2465, 2940, 3070],
                    2000 => [1525, 2565, 3100, 3195],
                    2250 => [1665, 2755, 3335, 3455],
                    2500 => [1805, 2945, 3570, 3715],
                    2750 => [1945, 3135, 3805, 3975],
                    3000 => [2085, 3325, 4040, 4235],
                    3250 => [2225, 3515, 4275, 4495],
                    3500 => [2365, 3705, 4510, 4755],
                    3750 => [2505, 3895, 4745, 5015],
                    4000 => [2645, 4085, 4980, 5275],
                    4250 => [2785, 4275, 5215, 5535],
                    4500 => [2925, 4465, 5450, 5795],
                    4750 => [3065, 4655, 5685, 6055],
                    5000 => [3205, 4845, 5920, 6315],
                ],
            ],

            '2022' => [
                'letter' => [
                    10 => [690, 690, 690, 690, 690, 690],
                    20 => [690, 690, 690, 690, 690, 690],
                    100 => [690, 690, 690, 775, 775, 775],
                ],
                'large-letter' => [
                    100 => [865, 865, 865, 960, 965, 965],
                    250 => [885, 885, 885, 1035, 1145, 1055],
                    500 => [985, 985, 985, 1220, 1400, 1250],
                    750 => [1025, 1025, 1025, 1405, 1680, 1450],
                ],
                'packet' => [
                    100 => [990, 995, 1020, 1125, 1230, 1215],
                    250 => [990, 995, 1020, 1220, 1365, 1345],
                    500 => [1115, 1120, 1160, 1585, 1805, 1810],
                    750 => [1215, 1225, 1275, 1825, 2085, 2085],
                    1000 => [1310, 1320, 1390, 2090, 2400, 2440],
                    1250 => [1400, 1400, 1450, 2275, 2640, 2760],
                    1500 => [1400, 1400, 1530, 2415, 2890, 2760],
                    2000 => [1400, 1465, 1580, 2515, 3050, 2760],
                ],
                'printed-papers' => [
                    100 => [990, 995, 1020, 1125, 1230, 1215],
                    250 => [990, 995, 1020, 1220, 1365, 1345],
                    500 => [1115, 1120, 1160, 1585, 1805, 1810],
                    750 => [1215, 1225, 1275, 1825, 2085, 2085],
                    1000 => [1310, 1320, 1390, 2090, 2400, 2440],
                    1250 => [1400, 1400, 1450, 2275, 2640, 2760],
                    1500 => [1400, 1400, 1530, 2415, 2890, 2760],
                    2000 => [1400, 1465, 1580, 2515, 3050, 2760],
                    2250 => [1540, 1605, 1720, 2705, 3285, 3020],
                    2500 => [1680, 1745, 1860, 2895, 3520, 3280],
                    2750 => [1820, 1885, 2000, 3085, 3755, 3540],
                    3000 => [1960, 2025, 2140, 3275, 3990, 3800],
                    3250 => [2100, 2165, 2280, 3465, 4225, 4060],
                    3500 => [2240, 2305, 2420, 3655, 4460, 4320],
                    3750 => [2380, 2445, 2560, 3845, 4695, 4580],
                    4000 => [2520, 2585, 2700, 4035, 4930, 4840],
                    4250 => [2660, 2725, 2840, 4225, 5165, 5100],
                    4500 => [2800, 2865, 2980, 4415, 5400, 5360],
                    4750 => [2940, 3005, 3120, 4605, 5635, 5620],
                    5000 => [3080, 3145, 3260, 4795, 5870, 5880],
                ],
            ],
        ];

        return self::getInternationalBoxPricing($bands, $country);
    }

    protected static function getInternationalSignedRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = ['AF', 'AL', 'DZ', 'AO', 'AI', 'AG', 'AM', 'AW', 'AU', 'AZ', 'BS', 'BH', 'BD', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BR', 'IO', 'VG', 'BN', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'CL', 'CN', 'CX', 'CO', 'KM', 'CG', 'CD', 'CR', 'CU', 'CW', 'DJ', 'DM', 'DO', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FJ', 'GF', 'PF', 'TF', 'GA', 'GM', 'GH', 'GD', 'GP', 'GT', 'GN', 'GW', 'GY', 'HT', 'HN', 'IN', 'IR', 'IQ', 'IL', 'CI', 'JM', 'JO', 'KZ', 'KE', 'KI', 'KW', 'KG', 'LA', 'LS', 'LR', 'LY', 'MO', 'MK', 'MG', 'YT', 'MW', 'MV', 'ML', 'MQ', 'MR', 'MU', 'MX', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NC', 'NI', 'NE', 'NG', 'NU', 'KP', 'NO', 'OM', 'PK', 'PW', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PR', 'QA', 'RE', 'RW', 'ST', 'SA', 'SN', 'SC', 'SL', 'SB', 'ZA', 'SS', 'LK', 'BQ', 'SH', 'KN', 'LC', 'MF', 'SX', 'VC', 'SD', 'SR', 'SZ', 'SY', 'TW', 'TJ', 'TZ', 'TL', 'TG', 'TK', 'TN', 'TM', 'TC', 'TV', 'UA', 'UY', 'UZ', 'VU', 'VE', 'VN', 'WF', 'EH', 'WS', 'YE', 'ZM', 'ZW'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    10 => [685, 685, 685, 685, 685],
                    20 => [685, 685, 685, 685, 685],
                    100 => [685, 685, 685, 785, 785],
                ],
                'large-letter' => [
                    100 => [875, 875, 875, 970, 980],
                    250 => [920, 920, 920, 1050, 1155],
                    500 => [995, 995, 995, 1230, 1410],
                    750 => [1035, 1035, 1035, 1415, 1690],
                ],
                'packet' => [
                    100 => [1050, 1050, 1050, 1200, 1305],
                    250 => [1065, 1065, 1065, 1295, 1440],
                    500 => [1210, 1210, 1210, 1660, 1880],
                    750 => [1315, 1315, 1315, 1900, 2160],
                    1000 => [1410, 1410, 1410, 2165, 2475],
                    1250 => [1450, 1450, 1450, 2350, 2715],
                    1500 => [1505, 1505, 1505, 2490, 2965],
                    2000 => [1525, 1525, 1525, 2535, 3070],
                ],
                'printed-papers' => [
                    100 => [1050, 1050, 1050, 1200, 1305],
                    250 => [1065, 1065, 1065, 1295, 1440],
                    500 => [1210, 1210, 1210, 1660, 1880],
                    750 => [1315, 1315, 1315, 1900, 2160],
                    1000 => [1410, 1410, 1410, 2165, 2475],
                    1250 => [1450, 1450, 1450, 2350, 2715],
                    1500 => [1505, 1505, 1505, 2490, 2965],
                    2000 => [1525, 1525, 1525, 2535, 3070],
                    2250 => [1665, 1665, 1665, 2725, 3305],
                    2500 => [1805, 1805, 1805, 2915, 3540],
                    2750 => [1945, 1945, 1945, 3105, 3775],
                    3000 => [2085, 2085, 2085, 3295, 4010],
                    3250 => [2225, 2225, 2225, 3485, 4245],
                    3500 => [2365, 2365, 2365, 3675, 4480],
                    3750 => [2505, 2505, 2505, 3865, 4715],
                    4000 => [2645, 2645, 2645, 4055, 4950],
                    4250 => [2785, 2785, 2785, 4245, 5185],
                    4500 => [2925, 2925, 2925, 4435, 5420],
                    4750 => [3065, 3065, 3065, 4625, 5655],
                    5000 => [3205, 3205, 3205, 4815, 5890],
                ],
            ],

            '2022' => [
                'letter' => [
                    10 => [700, 700, 700, 700, 700],
                    20 => [700, 700, 700, 700, 700],
                    100 => [700, 700, 700, 785, 785],
                ],
                'large-letter' => [
                    100 => [875, 875, 875, 970, 980],
                    250 => [920, 920, 920, 1050, 1155],
                    500 => [995, 995, 995, 1230, 1410],
                    750 => [1035, 1035, 1035, 1415, 1690],
                ],
                'packet' => [
                    100 => [1080, 1095, 1150, 1250, 1355],
                    250 => [1080, 1095, 1150, 1345, 1490],
                    500 => [1220, 1260, 1300, 1710, 1930],
                    750 => [1330, 1365, 1415, 1950, 2210],
                    1000 => [1430, 1460, 1530, 2215, 2525],
                    1250 => [1485, 1495, 1590, 2400, 2765],
                    1500 => [1500, 1520, 1650, 2540, 3015],
                    2000 => [1515, 1565, 1680, 2585, 3120],
                ],
                'printed-papers' => [
                    100 => [1080, 1095, 1150, 1250, 1355],
                    250 => [1080, 1095, 1150, 1345, 1490],
                    500 => [1220, 1260, 1300, 1710, 1930],
                    750 => [1330, 1365, 1415, 1950, 2210],
                    1000 => [1430, 1460, 1530, 2215, 2525],
                    1250 => [1485, 1495, 1590, 2400, 2765],
                    1500 => [1500, 1520, 1650, 2540, 3015],
                    2000 => [1515, 1565, 1680, 2585, 3120],
                    2250 => [1655, 1705, 1820, 2775, 3355],
                    2500 => [1795, 1845, 1960, 2965, 3590],
                    2750 => [1935, 1985, 2100, 3155, 3825],
                    3000 => [2075, 2125, 2240, 3345, 4060],
                    3250 => [2215, 2265, 2380, 3535, 4295],
                    3500 => [2355, 2405, 2520, 3725, 4530],
                    3750 => [2495, 2545, 2660, 3915, 4765],
                    4000 => [2635, 2685, 2800, 4105, 5000],
                    4250 => [2775, 2825, 2940, 4295, 5235],
                    4500 => [2915, 2965, 3080, 4485, 5470],
                    4750 => [3055, 3105, 3220, 4675, 5705],
                    5000 => [3195, 3245, 3360, 4865, 5940],
                ],
            ],
        ];

        return self::getInternationalBoxPricing($bands, $country);
    }

    protected static function getInternationalEconomyRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                'letter' => [
                    10 => 145,
                    20 => 145,
                    100 => 155,
                ],
                'large-letter' => [
                    100 => 300,
                    250 => 400,
                    500 => 490,
                    750 => 590,
                ],
                'packet' => [
                    100 => 485,
                    250 => 520,
                    500 => 745,
                    750 => 870,
                    1000 => 950,
                    1250 => 1055,
                    1500 => 1165,
                    1750 => 1280,
                    2000 => 1295,
                ],
            ],
            '2022' => [
                'letter' => [
                    10 => 160,
                    20 => 160,
                    100 => 160,
                ],
                'large-letter' => [
                    100 => 320,
                    250 => 460,
                    500 => 530,
                    750 => 635,
                ],
                'packet' => [
                    100 => 505,
                    250 => 540,
                    500 => 775,
                    750 => 905,
                    1000 => 1045,
                    1250 => 1160,
                    1500 => 1285,
                    1750 => 1425,
                    2000 => 1425,
                ],
                'printed-papers' => [
                    100 => 505,
                    250 => 540,
                    500 => 775,
                    750 => 905,
                    1000 => 1045,
                    1250 => 1160,
                    1500 => 1285,
                    1750 => 1425,
                    2000 => 1425,
                    2250 => 1575,
                    2500 => 1725,
                    2750 => 1875,
                    3000 => 2025,
                    3250 => 2175,
                    3500 => 2325,
                    3750 => 2475,
                    4000 => 2625,
                    4250 => 2775,
                    4500 => 2925,
                    4750 => 3075,
                    5000 => 3225,
                ],
            ],
        ];

        return self::getBoxPricing(self::$internationalDefaultBox, $bands, 20);
    }

    protected static function getParcelforceIrelandexpressRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '5' => [
                    500 => 2754,
                    1000 => 2754,
                    1500 => 2754,
                    2000 => 2754,
                    2500 => 2754,
                    3000 => 2754,
                    3500 => 2754,
                    4000 => 2754,
                    4500 => 2754,
                    5000 => 2754,
                    5500 => 2886,
                    6000 => 3018,
                    6500 => 3150,
                    7000 => 3282,
                    7500 => 3414,
                    8000 => 3546,
                    8500 => 3678,
                    9000 => 3810,
                    9500 => 3942,
                    10000 => 4074,
                    10500 => 4158,
                    11000 => 4242,
                    11500 => 4326,
                    12000 => 4410,
                    12500 => 4494,
                    13000 => 4578,
                    13500 => 4662,
                    14000 => 4746,
                    14500 => 4830,
                    15000 => 4914,
                    15500 => 5022,
                    16000 => 5130,
                    16500 => 5238,
                    17000 => 5346,
                    17500 => 5454,
                    18000 => 5562,
                    18500 => 5670,
                    19000 => 5778,
                    19500 => 5886,
                    20000 => 5994,
                    20500 => 6102,
                    21000 => 6210,
                    21500 => 6318,
                    22000 => 6426,
                    22500 => 6534,
                    23000 => 6642,
                    23500 => 6750,
                    24000 => 6858,
                    24500 => 6966,
                    25000 => 7074,
                    25500 => 7182,
                    26000 => 7290,
                    26500 => 7398,
                    27000 => 7506,
                    27500 => 7614,
                    28000 => 7722,
                    28500 => 7830,
                    29000 => 7938,
                    29500 => 8046,
                    30000 => 8154,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 200,
            'maximumTotalCover' => 2500,
        ]);
    }

    protected static function getParcelforceEuropriorityRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '4' => [
                    500 => 4248,
                    1000 => 4320,
                    1500 => 4392,
                    2000 => 4464,
                    2500 => 4536,
                    3000 => 4620,
                    3500 => 4704,
                    4000 => 4788,
                    4500 => 4872,
                    5000 => 4956,
                    5500 => 4968,
                    6000 => 4980,
                    6500 => 4992,
                    7000 => 5004,
                    7500 => 5016,
                    8000 => 5028,
                    8500 => 5040,
                    9000 => 5052,
                    9500 => 5064,
                    10000 => 5076,
                    10500 => 5118,
                    11000 => 5160,
                    11500 => 5202,
                    12000 => 5244,
                    12500 => 5286,
                    13000 => 5328,
                    13500 => 5370,
                    14000 => 5412,
                    14500 => 5454,
                    15000 => 5496,
                    15500 => 5520,
                    16000 => 5544,
                    16500 => 5568,
                    17000 => 5592,
                    17500 => 5616,
                    18000 => 5640,
                    18500 => 5664,
                    19000 => 5688,
                    19500 => 5712,
                    20000 => 5736,
                    20500 => 5760,
                    21000 => 5784,
                    21500 => 5808,
                    22000 => 5832,
                    22500 => 5856,
                    23000 => 5880,
                    23500 => 5904,
                    24000 => 5928,
                    24500 => 5952,
                    25000 => 5976,
                    25500 => 6000,
                    26000 => 6024,
                    26500 => 6048,
                    27000 => 6072,
                    27500 => 6096,
                    28000 => 6120,
                    28500 => 6144,
                    29000 => 6168,
                    29500 => 6192,
                    30000 => 6216,
                ],
                '5' => [
                    500 => 4368,
                    1000 => 4470,
                    1500 => 4572,
                    2000 => 4674,
                    2500 => 4776,
                    3000 => 4974,
                    3500 => 5172,
                    4000 => 5370,
                    4500 => 5568,
                    5000 => 5766,
                    5500 => 5898,
                    6000 => 6030,
                    6500 => 6162,
                    7000 => 6294,
                    7500 => 6426,
                    8000 => 6558,
                    8500 => 6690,
                    9000 => 6822,
                    9500 => 6954,
                    10000 => 7086,
                    10500 => 7194,
                    11000 => 7302,
                    11500 => 7410,
                    12000 => 7518,
                    12500 => 7626,
                    13000 => 7734,
                    13500 => 7842,
                    14000 => 7950,
                    14500 => 8058,
                    15000 => 8166,
                    15500 => 8238,
                    16000 => 8310,
                    16500 => 8382,
                    17000 => 8454,
                    17500 => 8526,
                    18000 => 8598,
                    18500 => 8670,
                    19000 => 8742,
                    19500 => 8814,
                    20000 => 8886,
                    20500 => 9078,
                    21000 => 9030,
                    21500 => 9102,
                    22000 => 9174,
                    22500 => 9246,
                    23000 => 9318,
                    23500 => 9390,
                    24000 => 9462,
                    24500 => 9534,
                    25000 => 9606,
                    25500 => 9678,
                    26000 => 9750,
                    26500 => 9822,
                    27000 => 9894,
                    27500 => 9966,
                    28000 => 10038,
                    28500 => 10110,
                    29000 => 10182,
                    29500 => 10254,
                    30000 => 10326,
                ],
                '6' => [
                    500 => 3876,
                    1000 => 4092,
                    1500 => 4308,
                    2000 => 4524,
                    2500 => 4740,
                    3000 => 4938,
                    3500 => 5136,
                    4000 => 5334,
                    4500 => 5532,
                    5000 => 5730,
                    5500 => 5856,
                    6000 => 5982,
                    6500 => 6108,
                    7000 => 6234,
                    7500 => 6360,
                    8000 => 6486,
                    8500 => 6612,
                    9000 => 6738,
                    9500 => 6864,
                    10000 => 6990,
                    10500 => 7116,
                    11000 => 7242,
                    11500 => 7368,
                    12000 => 7494,
                    12500 => 7620,
                    13000 => 7746,
                    13500 => 7872,
                    14000 => 7998,
                    14500 => 8124,
                    15000 => 8250,
                    15500 => 8322,
                    16000 => 8394,
                    16500 => 8466,
                    17000 => 8538,
                    17500 => 8610,
                    18000 => 8682,
                    18500 => 8754,
                    19000 => 8826,
                    19500 => 8898,
                    20000 => 8970,
                    20500 => 9042,
                    21000 => 9114,
                    21500 => 9186,
                    22000 => 9258,
                    22500 => 9330,
                    23000 => 9402,
                    23500 => 9474,
                    24000 => 9546,
                    24500 => 9618,
                    25000 => 9690,
                    25500 => 9762,
                    26000 => 9834,
                    26500 => 9906,
                    27000 => 9978,
                    27500 => 10050,
                    28000 => 10122,
                    28500 => 10194,
                    29000 => 10266,
                    29500 => 10338,
                    30000 => 10410,
                ],
                '7' => [
                    500 => 4254,
                    1000 => 4506,
                    1500 => 4758,
                    2000 => 5010,
                    2500 => 5262,
                    3000 => 5490,
                    3500 => 5718,
                    4000 => 5946,
                    4500 => 6174,
                    5000 => 6402,
                    5500 => 6546,
                    6000 => 6690,
                    6500 => 6834,
                    7000 => 6978,
                    7500 => 7122,
                    8000 => 7266,
                    8500 => 7410,
                    9000 => 7554,
                    9500 => 7698,
                    10000 => 7842,
                    10500 => 7962,
                    11000 => 8082,
                    11500 => 8202,
                    12000 => 8322,
                    12500 => 8442,
                    13000 => 8562,
                    13500 => 8682,
                    14000 => 8802,
                    14500 => 8922,
                    15000 => 9042,
                    15500 => 9108,
                    16000 => 9174,
                    16500 => 9240,
                    17000 => 9306,
                    17500 => 9372,
                    18000 => 9438,
                    18500 => 9504,
                    19000 => 9570,
                    19500 => 9636,
                    20000 => 9702,
                    20500 => 9768,
                    21000 => 9834,
                    21500 => 9900,
                    22000 => 9966,
                    22500 => 10032,
                    23000 => 10098,
                    23500 => 10164,
                    24000 => 10230,
                    24500 => 10296,
                    25000 => 10362,
                    25500 => 10428,
                    26000 => 10494,
                    26500 => 10560,
                    27000 => 10626,
                    27500 => 10692,
                    28000 => 10758,
                    28500 => 10824,
                    29000 => 10890,
                    29500 => 10956,
                    30000 => 11022,
                ],
                '8' => [
                    500 => 4620,
                    1000 => 4860,
                    1500 => 5100,
                    2000 => 5340,
                    2500 => 5580,
                    3000 => 5844,
                    3500 => 6108,
                    4000 => 6372,
                    4500 => 6636,
                    5000 => 6900,
                    5500 => 7074,
                    6000 => 7248,
                    6500 => 7422,
                    7000 => 7596,
                    7500 => 7770,
                    8000 => 7944,
                    8500 => 8118,
                    9000 => 8292,
                    9500 => 8466,
                    10000 => 8640,
                    10500 => 8814,
                    11000 => 8988,
                    11500 => 9162,
                    12000 => 9336,
                    12500 => 9510,
                    13000 => 9684,
                    13500 => 9858,
                    14000 => 10032,
                    14500 => 10206,
                    15000 => 10380,
                    15500 => 10452,
                    16000 => 10524,
                    16500 => 10596,
                    17000 => 10668,
                    17500 => 10740,
                    18000 => 10812,
                    18500 => 10884,
                    19000 => 10956,
                    19500 => 11028,
                    20000 => 11100,
                    20500 => 11172,
                    21000 => 11244,
                    21500 => 11316,
                    22000 => 11388,
                    22500 => 11460,
                    23000 => 11532,
                    23500 => 11604,
                    24000 => 11676,
                    24500 => 11748,
                    25000 => 11820,
                    25500 => 11892,
                    26000 => 11964,
                    26500 => 12036,
                    27000 => 12108,
                    27500 => 12180,
                    28000 => 12252,
                    28500 => 12324,
                    29000 => 12396,
                    29500 => 12468,
                    30000 => 12540,
                ],
                '9' => [
                    500 => 5244,
                    1000 => 5544,
                    1500 => 5844,
                    2000 => 6144,
                    2500 => 6444,
                    3000 => 6732,
                    3500 => 7020,
                    4000 => 7308,
                    4500 => 7596,
                    5000 => 7884,
                    5500 => 8052,
                    6000 => 8220,
                    6500 => 8388,
                    7000 => 8556,
                    7500 => 8724,
                    8000 => 8892,
                    8500 => 9060,
                    9000 => 9228,
                    9500 => 9396,
                    10000 => 9564,
                    10500 => 9714,
                    11000 => 9864,
                    11500 => 10014,
                    12000 => 10164,
                    12500 => 10314,
                    13000 => 10464,
                    13500 => 10614,
                    14000 => 10764,
                    14500 => 10914,
                    15000 => 11064,
                    15500 => 11160,
                    16000 => 11256,
                    16500 => 11352,
                    17000 => 11448,
                    17500 => 11544,
                    18000 => 11640,
                    18500 => 11736,
                    19000 => 11832,
                    19500 => 11928,
                    20000 => 12024,
                    20500 => 12120,
                    21000 => 12216,
                    21500 => 12312,
                    22000 => 12408,
                    22500 => 12504,
                    23000 => 12600,
                    23500 => 12696,
                    24000 => 12792,
                    24500 => 12888,
                    25000 => 12984,
                    25500 => 13080,
                    26000 => 13176,
                    26500 => 13272,
                    27000 => 13368,
                    27500 => 13464,
                    28000 => 13560,
                    28500 => 13656,
                    29000 => 13752,
                    29500 => 13848,
                    30000 => 13944,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 100,
            'maximumTotalCover' => 2500,
        ]);
    }

    protected static function getParcelforceGlobaleconomyRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '4' => [
                    500 => 4620,
                    1000 => 4746,
                    1500 => 4872,
                    2000 => 4998,
                    2500 => 5124,
                    3000 => 5184,
                    3500 => 5244,
                    4000 => 5304,
                    4500 => 5364,
                    5000 => 5424,
                    5500 => 5460,
                    6000 => 5496,
                    6500 => 5532,
                    7000 => 5568,
                    7500 => 5604,
                    8000 => 5640,
                    8500 => 5676,
                    9000 => 5712,
                    9500 => 5748,
                    10000 => 5784,
                    10500 => 5796,
                    11000 => 5808,
                    11500 => 5820,
                    12000 => 5832,
                    12500 => 5844,
                    13000 => 5856,
                    13500 => 5868,
                    14000 => 5880,
                    14500 => 5892,
                    15000 => 5904,
                    15500 => 5940,
                    16000 => 5976,
                    16500 => 6012,
                    17000 => 6048,
                    17500 => 6084,
                    18000 => 6120,
                    18500 => 6156,
                    19000 => 6192,
                    19500 => 6228,
                    20000 => 6264,
                    20500 => 6300,
                    21000 => 6336,
                    21500 => 6372,
                    22000 => 6408,
                    22500 => 6444,
                    23000 => 6480,
                    23500 => 6516,
                    24000 => 6552,
                    24500 => 6588,
                    25000 => 6624,
                    25500 => 6660,
                    26000 => 6696,
                    26500 => 6732,
                    27000 => 6768,
                    27500 => 6804,
                    28000 => 6840,
                    28500 => 6876,
                    29000 => 6912,
                    29500 => 6948,
                    30000 => 6984,
                ],
                '5' => [
                    500 => 4740,
                    1000 => 4908,
                    1500 => 5076,
                    2000 => 5244,
                    2500 => 5412,
                    3000 => 5598,
                    3500 => 5784,
                    4000 => 5970,
                    4500 => 6156,
                    5000 => 6342,
                    5500 => 6492,
                    6000 => 6642,
                    6500 => 6792,
                    7000 => 6942,
                    7500 => 7092,
                    8000 => 7242,
                    8500 => 7392,
                    9000 => 7542,
                    9500 => 7692,
                    10000 => 7842,
                    10500 => 7950,
                    11000 => 8058,
                    11500 => 8166,
                    12000 => 8274,
                    12500 => 8382,
                    13000 => 8490,
                    13500 => 8598,
                    14000 => 8706,
                    14500 => 8814,
                    15000 => 8922,
                    15500 => 9018,
                    16000 => 9114,
                    16500 => 9210,
                    17000 => 9306,
                    17500 => 9402,
                    18000 => 9498,
                    18500 => 9594,
                    19000 => 9690,
                    19500 => 9786,
                    20000 => 9882,
                    20500 => 9978,
                    21000 => 10074,
                    21500 => 10170,
                    22000 => 10266,
                    22500 => 10362,
                    23000 => 10458,
                    23500 => 10554,
                    24000 => 10650,
                    24500 => 10746,
                    25000 => 10842,
                    25500 => 10938,
                    26000 => 11034,
                    26500 => 11130,
                    27000 => 11226,
                    27500 => 11322,
                    28000 => 11418,
                    28500 => 11514,
                    29000 => 11610,
                    29500 => 11706,
                    30000 => 11802,
                ],
                '6' => [
                    500 => 4368,
                    1000 => 4572,
                    1500 => 4776,
                    2000 => 4980,
                    2500 => 5184,
                    3000 => 5418,
                    3500 => 5652,
                    4000 => 5886,
                    4500 => 6120,
                    5000 => 6354,
                    5500 => 6492,
                    6000 => 6630,
                    6500 => 6768,
                    7000 => 6906,
                    7500 => 7044,
                    8000 => 7182,
                    8500 => 7320,
                    9000 => 7458,
                    9500 => 7596,
                    10000 => 7734,
                    10500 => 7866,
                    11000 => 7998,
                    11500 => 8130,
                    12000 => 8262,
                    12500 => 8394,
                    13000 => 8526,
                    13500 => 8658,
                    14000 => 8790,
                    14500 => 8922,
                    15000 => 9054,
                    15500 => 9138,
                    16000 => 9222,
                    16500 => 9306,
                    17000 => 9390,
                    17500 => 9474,
                    18000 => 9558,
                    18500 => 9642,
                    19000 => 9726,
                    19500 => 9810,
                    20000 => 9894,
                    20500 => 9978,
                    21000 => 10062,
                    21500 => 10146,
                    22000 => 10230,
                    22500 => 10314,
                    23000 => 10398,
                    23500 => 10482,
                    24000 => 10566,
                    24500 => 10650,
                    25000 => 10734,
                    25500 => 10818,
                    26000 => 10902,
                    26500 => 10986,
                    27000 => 11070,
                    27500 => 11154,
                    28000 => 11238,
                    28500 => 11322,
                    29000 => 11406,
                    29500 => 11490,
                    30000 => 11574,
                ],
                '7' => [
                    500 => 4620,
                    1000 => 4920,
                    1500 => 5220,
                    2000 => 5520,
                    2500 => 5820,
                    3000 => 6072,
                    3500 => 6324,
                    4000 => 6576,
                    4500 => 6828,
                    5000 => 7080,
                    5500 => 7248,
                    6000 => 7416,
                    6500 => 7584,
                    7000 => 7752,
                    7500 => 7920,
                    8000 => 8088,
                    8500 => 8256,
                    9000 => 8424,
                    9500 => 8592,
                    10000 => 8760,
                    10500 => 8904,
                    11000 => 9048,
                    11500 => 9192,
                    12000 => 9336,
                    12500 => 9480,
                    13000 => 9624,
                    13500 => 9768,
                    14000 => 9912,
                    14500 => 10056,
                    15000 => 10200,
                    15500 => 10272,
                    16000 => 10344,
                    16500 => 10416,
                    17000 => 10488,
                    17500 => 10560,
                    18000 => 10632,
                    18500 => 10704,
                    19000 => 10776,
                    19500 => 10848,
                    20000 => 10920,
                    20500 => 10992,
                    21000 => 11064,
                    21500 => 11136,
                    22000 => 11208,
                    22500 => 11280,
                    23000 => 11352,
                    23500 => 11424,
                    24000 => 11496,
                    24500 => 11568,
                    25000 => 11640,
                    25500 => 11712,
                    26000 => 11784,
                    26500 => 11856,
                    27000 => 11928,
                    27500 => 12000,
                    28000 => 12072,
                    28500 => 12144,
                    29000 => 12216,
                    29500 => 12288,
                    30000 => 12360,
                ],
                '8' => [
                    500 => 5118,
                    1000 => 5382,
                    1500 => 5646,
                    2000 => 5910,
                    2500 => 6174,
                    3000 => 6474,
                    3500 => 6774,
                    4000 => 7074,
                    4500 => 7374,
                    5000 => 7674,
                    5500 => 7860,
                    6000 => 8046,
                    6500 => 8232,
                    7000 => 8418,
                    7500 => 8604,
                    8000 => 8790,
                    8500 => 8976,
                    9000 => 9162,
                    9500 => 9348,
                    10000 => 9534,
                    10500 => 9726,
                    11000 => 9918,
                    11500 => 10110,
                    12000 => 10302,
                    12500 => 10494,
                    13000 => 10686,
                    13500 => 10878,
                    14000 => 11070,
                    14500 => 11262,
                    15000 => 11454,
                    15500 => 11538,
                    16000 => 11622,
                    16500 => 11706,
                    17000 => 11790,
                    17500 => 11874,
                    18000 => 11958,
                    18500 => 12042,
                    19000 => 12126,
                    19500 => 12210,
                    20000 => 12294,
                    20500 => 12378,
                    21000 => 12462,
                    21500 => 12546,
                    22000 => 12630,
                    22500 => 12714,
                    23000 => 12798,
                    23500 => 12882,
                    24000 => 12966,
                    24500 => 13050,
                    25000 => 13134,
                    25500 => 13218,
                    26000 => 13302,
                    26500 => 13386,
                    27000 => 13470,
                    27500 => 13554,
                    28000 => 13638,
                    28500 => 13722,
                    29000 => 13806,
                    29500 => 13890,
                    30000 => 13974,
                ],
                '9' => [
                    500 => 5742,
                    1000 => 6090,
                    1500 => 6438,
                    2000 => 6786,
                    2500 => 7134,
                    3000 => 7446,
                    3500 => 7758,
                    4000 => 8070,
                    4500 => 8382,
                    5000 => 8694,
                    5500 => 8886,
                    6000 => 9078,
                    6500 => 9270,
                    7000 => 9462,
                    7500 => 9654,
                    8000 => 9846,
                    8500 => 10038,
                    9000 => 10230,
                    9500 => 10422,
                    10000 => 10614,
                    10500 => 10764,
                    11000 => 10914,
                    11500 => 11064,
                    12000 => 11214,
                    12500 => 11364,
                    13000 => 11514,
                    13500 => 11664,
                    14000 => 11814,
                    14500 => 11964,
                    15000 => 12114,
                    15500 => 12222,
                    16000 => 12330,
                    16500 => 12438,
                    17000 => 12546,
                    17500 => 12654,
                    18000 => 12762,
                    18500 => 12870,
                    19000 => 12978,
                    19500 => 13086,
                    20000 => 13194,
                    20500 => 13302,
                    21000 => 13410,
                    21500 => 13518,
                    22000 => 13626,
                    22500 => 13734,
                    23000 => 13842,
                    23500 => 13950,
                    24000 => 14058,
                    24500 => 14166,
                    25000 => 14274,
                    25500 => 14382,
                    26000 => 14490,
                    26500 => 14598,
                    27000 => 14706,
                    27500 => 14814,
                    28000 => 14922,
                    28500 => 15030,
                    29000 => 15138,
                    29500 => 15246,
                    30000 => 15354,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 0,
            'maximumTotalCover' => 0,
        ]);
    }

    protected static function getParcelforceGlobalexpressRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '4' => [
                    500 => 6000,
                    1000 => 6282,
                    1500 => 6564,
                    2000 => 6846,
                    2500 => 7128,
                    3000 => 7422,
                    3500 => 7716,
                    4000 => 8010,
                    4500 => 8304,
                    5000 => 8598,
                    5500 => 8892,
                    6000 => 9186,
                    6500 => 9480,
                    7000 => 9774,
                    7500 => 10068,
                    8000 => 10362,
                    8500 => 10656,
                    9000 => 10950,
                    9500 => 11244,
                    10000 => 11538,
                    10500 => 11808,
                    11000 => 12078,
                    11500 => 12348,
                    12000 => 12618,
                    12500 => 12888,
                    13000 => 13158,
                    13500 => 13428,
                    14000 => 13698,
                    14500 => 13968,
                    15000 => 14238,
                    15500 => 14508,
                    16000 => 14778,
                    16500 => 15048,
                    17000 => 15318,
                    17500 => 15588,
                    18000 => 15858,
                    18500 => 16128,
                    19000 => 16398,
                    19500 => 16668,
                    20000 => 16938,
                    20500 => 17208,
                    21000 => 17478,
                    21500 => 17748,
                    22000 => 18018,
                    22500 => 18288,
                    23000 => 18558,
                    23500 => 18828,
                    24000 => 19098,
                    24500 => 19368,
                    25000 => 19638,
                    25500 => 19908,
                    26000 => 20178,
                    26500 => 20448,
                    27000 => 20718,
                    27500 => 20988,
                    28000 => 21258,
                    28500 => 21528,
                    29000 => 21798,
                    29500 => 22068,
                    30000 => 22338,
                ],
                '5' => [
                    500 => 5460,
                    1000 => 5742,
                    1500 => 6024,
                    2000 => 6306,
                    2500 => 6588,
                    3000 => 6870,
                    3500 => 7152,
                    4000 => 7434,
                    4500 => 7716,
                    5000 => 7998,
                    5500 => 8190,
                    6000 => 8382,
                    6500 => 8574,
                    7000 => 8766,
                    7500 => 8958,
                    8000 => 9150,
                    8500 => 9342,
                    9000 => 9534,
                    9500 => 9726,
                    10000 => 9918,
                    10500 => 10182,
                    11000 => 10446,
                    11500 => 10710,
                    12000 => 10974,
                    12500 => 11238,
                    13000 => 11502,
                    13500 => 11766,
                    14000 => 12030,
                    14500 => 12294,
                    15000 => 12558,
                    15500 => 12756,
                    16000 => 12954,
                    16500 => 13152,
                    17000 => 13350,
                    17500 => 13548,
                    18000 => 13746,
                    18500 => 13944,
                    19000 => 14142,
                    19500 => 14340,
                    20000 => 14538,
                    20500 => 14736,
                    21000 => 14934,
                    21500 => 15132,
                    22000 => 15330,
                    22500 => 15528,
                    23000 => 15726,
                    23500 => 15924,
                    24000 => 16122,
                    24500 => 16320,
                    25000 => 16518,
                    25500 => 16716,
                    26000 => 16914,
                    26500 => 17112,
                    27000 => 17310,
                    27500 => 17508,
                    28000 => 17706,
                    28500 => 17904,
                    29000 => 18102,
                    29500 => 18300,
                    30000 => 18498,
                ],
                '6' => [
                    500 => 5112,
                    1000 => 5454,
                    1500 => 5796,
                    2000 => 6138,
                    2500 => 6480,
                    3000 => 6756,
                    3500 => 7032,
                    4000 => 7308,
                    4500 => 7584,
                    5000 => 7860,
                    5500 => 8082,
                    6000 => 8304,
                    6500 => 8526,
                    7000 => 8748,
                    7500 => 8970,
                    8000 => 9192,
                    8500 => 9414,
                    9000 => 9636,
                    9500 => 9858,
                    10000 => 10080,
                    10500 => 10302,
                    11000 => 10524,
                    11500 => 10746,
                    12000 => 10968,
                    12500 => 11190,
                    13000 => 11412,
                    13500 => 11634,
                    14000 => 11856,
                    14500 => 12078,
                    15000 => 12300,
                    15500 => 12486,
                    16000 => 12672,
                    16500 => 12858,
                    17000 => 13044,
                    17500 => 13230,
                    18000 => 13416,
                    18500 => 13602,
                    19000 => 13788,
                    19500 => 13974,
                    20000 => 14160,
                    20500 => 14346,
                    21000 => 14532,
                    21500 => 14718,
                    22000 => 14904,
                    22500 => 15090,
                    23000 => 15276,
                    23500 => 15462,
                    24000 => 15648,
                    24500 => 15834,
                    25000 => 16020,
                    25500 => 16206,
                    26000 => 16392,
                    26500 => 16578,
                    27000 => 16764,
                    27500 => 16950,
                    28000 => 17136,
                    28500 => 17322,
                    29000 => 17508,
                    29500 => 17694,
                    30000 => 17880,
                ],
                '7' => [
                    500 => 5160,
                    1000 => 5556,
                    1500 => 5952,
                    2000 => 6348,
                    2500 => 6744,
                    3000 => 7074,
                    3500 => 7404,
                    4000 => 7734,
                    4500 => 8064,
                    5000 => 8394,
                    5500 => 8670,
                    6000 => 8946,
                    6500 => 9222,
                    7000 => 9498,
                    7500 => 9774,
                    8000 => 10050,
                    8500 => 10326,
                    9000 => 10602,
                    9500 => 10878,
                    10000 => 11154,
                    10500 => 11370,
                    11000 => 11586,
                    11500 => 11802,
                    12000 => 12018,
                    12500 => 12234,
                    13000 => 12450,
                    13500 => 12666,
                    14000 => 12882,
                    14500 => 13098,
                    15000 => 13314,
                    15500 => 13518,
                    16000 => 13722,
                    16500 => 13926,
                    17000 => 14130,
                    17500 => 14334,
                    18000 => 14538,
                    18500 => 14742,
                    19000 => 14946,
                    19500 => 15150,
                    20000 => 15354,
                    20500 => 15558,
                    21000 => 15762,
                    21500 => 15966,
                    22000 => 16170,
                    22500 => 16374,
                    23000 => 16578,
                    23500 => 16782,
                    24000 => 16986,
                    24500 => 17190,
                    25000 => 17394,
                    25500 => 17598,
                    26000 => 17802,
                    26500 => 18006,
                    27000 => 18210,
                    27500 => 18414,
                    28000 => 18618,
                    28500 => 18822,
                    29000 => 19026,
                    29500 => 19230,
                    30000 => 19434,
                ],
                '8' => [
                    500 => 5580,
                    1000 => 5982,
                    1500 => 6384,
                    2000 => 6786,
                    2500 => 7188,
                    3000 => 7542,
                    3500 => 7896,
                    4000 => 8250,
                    4500 => 8604,
                    5000 => 8958,
                    5500 => 9300,
                    6000 => 9642,
                    6500 => 9984,
                    7000 => 10326,
                    7500 => 10668,
                    8000 => 11010,
                    8500 => 11352,
                    9000 => 11694,
                    9500 => 12036,
                    10000 => 12378,
                    10500 => 12690,
                    11000 => 13002,
                    11500 => 13314,
                    12000 => 13626,
                    12500 => 13938,
                    13000 => 14250,
                    13500 => 14562,
                    14000 => 14874,
                    14500 => 15186,
                    15000 => 15498,
                    15500 => 15786,
                    16000 => 16074,
                    16500 => 16362,
                    17000 => 16650,
                    17500 => 16938,
                    18000 => 17226,
                    18500 => 17514,
                    19000 => 17802,
                    19500 => 18090,
                    20000 => 18378,
                    20500 => 18666,
                    21000 => 18954,
                    21500 => 19242,
                    22000 => 19530,
                    22500 => 19818,
                    23000 => 20106,
                    23500 => 20394,
                    24000 => 20682,
                    24500 => 20970,
                    25000 => 21258,
                    25500 => 21546,
                    26000 => 21834,
                    26500 => 22122,
                    27000 => 22410,
                    27500 => 22698,
                    28000 => 22986,
                    28500 => 23274,
                    29000 => 23562,
                    29500 => 23850,
                    30000 => 24138,
                ],
                '9' => [
                    500 => 5988,
                    1000 => 6558,
                    1500 => 7128,
                    2000 => 7698,
                    2500 => 8268,
                    3000 => 8802,
                    3500 => 9336,
                    4000 => 9870,
                    4500 => 10404,
                    5000 => 10938,
                    5500 => 11364,
                    6000 => 11790,
                    6500 => 12216,
                    7000 => 12642,
                    7500 => 13068,
                    8000 => 13494,
                    8500 => 13920,
                    9000 => 14346,
                    9500 => 14772,
                    10000 => 15198,
                    10500 => 15582,
                    11000 => 15966,
                    11500 => 16350,
                    12000 => 16734,
                    12500 => 17118,
                    13000 => 17502,
                    13500 => 17886,
                    14000 => 18270,
                    14500 => 18654,
                    15000 => 19038,
                    15500 => 19446,
                    16000 => 19854,
                    16500 => 20262,
                    17000 => 20670,
                    17500 => 21078,
                    18000 => 21486,
                    18500 => 21894,
                    19000 => 22302,
                    19500 => 22710,
                    20000 => 23118,
                    20500 => 23526,
                    21000 => 23934,
                    21500 => 24342,
                    22000 => 24750,
                    22500 => 25158,
                    23000 => 25566,
                    23500 => 25974,
                    24000 => 26382,
                    24500 => 26790,
                    25000 => 27198,
                    25500 => 27606,
                    26000 => 28014,
                    26500 => 28422,
                    27000 => 28830,
                    27500 => 29238,
                    28000 => 29646,
                    28500 => 30054,
                    29000 => 30462,
                    29500 => 30870,
                    30000 => 31278,
                ],
                '9_NON_EU' => [
                    500 => 5988,
                    1000 => 6558,
                    1500 => 7128,
                    2000 => 7698,
                    2500 => 8268,
                    3000 => 8802,
                    3500 => 9336,
                    4000 => 9870,
                    4500 => 10404,
                    5000 => 10938,
                    5500 => 11364,
                    6000 => 11790,
                    6500 => 12216,
                    7000 => 12642,
                    7500 => 13068,
                    8000 => 13494,
                    8500 => 13920,
                    9000 => 14346,
                    9500 => 14772,
                    10000 => 15198,
                    10500 => 15582,
                    11000 => 15966,
                    11500 => 16350,
                    12000 => 16734,
                    12500 => 17118,
                    13000 => 17502,
                    13500 => 17886,
                    14000 => 18270,
                    14500 => 18654,
                    15000 => 19038,
                    15500 => 19446,
                    16000 => 19854,
                    16500 => 20262,
                    17000 => 20670,
                    17500 => 21078,
                    18000 => 21486,
                    18500 => 21894,
                    19000 => 22302,
                    19500 => 22710,
                    20000 => 23118,
                    20500 => 23526,
                    21000 => 23934,
                    21500 => 24342,
                    22000 => 24750,
                    22500 => 25158,
                    23000 => 25566,
                    23500 => 25974,
                    24000 => 26382,
                    24500 => 26790,
                    25000 => 27198,
                    25500 => 27606,
                    26000 => 28014,
                    26500 => 28422,
                    27000 => 28830,
                    27500 => 29238,
                    28000 => 29646,
                    28500 => 30054,
                    29000 => 30462,
                    29500 => 30870,
                    30000 => 31278,
                ],
                '10' => [
                    500 => 6180,
                    1000 => 6780,
                    1500 => 7380,
                    2000 => 7980,
                    2500 => 8580,
                    3000 => 9018,
                    3500 => 9456,
                    4000 => 9894,
                    4500 => 10332,
                    5000 => 10770,
                    5500 => 11220,
                    6000 => 11670,
                    6500 => 12120,
                    7000 => 12570,
                    7500 => 13020,
                    8000 => 13470,
                    8500 => 13920,
                    9000 => 14370,
                    9500 => 14820,
                    10000 => 15270,
                    10500 => 15624,
                    11000 => 15978,
                    11500 => 16332,
                    12000 => 16686,
                    12500 => 17040,
                    13000 => 17394,
                    13500 => 17748,
                    14000 => 18102,
                    14500 => 18456,
                    15000 => 18810,
                    15500 => 19164,
                    16000 => 19518,
                    16500 => 19872,
                    17000 => 20226,
                    17500 => 20580,
                    18000 => 20934,
                    18500 => 21288,
                    19000 => 21642,
                    19500 => 21996,
                    20000 => 22350,
                    20500 => 22704,
                    21000 => 23058,
                    21500 => 23412,
                    22000 => 23766,
                    22500 => 24120,
                    23000 => 24474,
                    23500 => 24828,
                    24000 => 25182,
                    24500 => 25536,
                    25000 => 25890,
                    25500 => 26244,
                    26000 => 26598,
                    26500 => 26952,
                    27000 => 27306,
                    27500 => 27660,
                    28000 => 28014,
                    28500 => 28368,
                    29000 => 28722,
                    29500 => 29076,
                    30000 => 29430,
                ],
                '11' => [
                    500 => 7410,
                    1000 => 8166,
                    1500 => 8922,
                    2000 => 9678,
                    2500 => 10434,
                    3000 => 11112,
                    3500 => 11790,
                    4000 => 12468,
                    4500 => 13146,
                    5000 => 13824,
                    5500 => 14418,
                    6000 => 15012,
                    6500 => 15606,
                    7000 => 16200,
                    7500 => 16794,
                    8000 => 17388,
                    8500 => 17982,
                    9000 => 18576,
                    9500 => 19170,
                    10000 => 19764,
                    10500 => 20352,
                    11000 => 20940,
                    11500 => 21528,
                    12000 => 22116,
                    12500 => 22704,
                    13000 => 23292,
                    13500 => 23880,
                    14000 => 24468,
                    14500 => 25056,
                    15000 => 25644,
                    15500 => 26232,
                    16000 => 26820,
                    16500 => 27408,
                    17000 => 27996,
                    17500 => 28584,
                    18000 => 29172,
                    18500 => 29760,
                    19000 => 30348,
                    19500 => 30936,
                    20000 => 31524,
                    20500 => 32112,
                    21000 => 32700,
                    21500 => 33288,
                    22000 => 33876,
                    22500 => 34464,
                    23000 => 35052,
                    23500 => 35640,
                    24000 => 36228,
                    24500 => 36816,
                    25000 => 37404,
                    25500 => 37992,
                    26000 => 38580,
                    26500 => 39168,
                    27000 => 39756,
                    27500 => 40344,
                    28000 => 40932,
                    28500 => 41520,
                    29000 => 42108,
                    29500 => 42696,
                    30000 => 43284,
                ],
                '12' => [
                    500 => 8400,
                    1000 => 9498,
                    1500 => 10596,
                    2000 => 11694,
                    2500 => 12792,
                    3000 => 13704,
                    3500 => 14616,
                    4000 => 15528,
                    4500 => 16440,
                    5000 => 17352,
                    5500 => 18096,
                    6000 => 18840,
                    6500 => 19584,
                    7000 => 20328,
                    7500 => 21072,
                    8000 => 21816,
                    8500 => 22560,
                    9000 => 23304,
                    9500 => 24048,
                    10000 => 24792,
                    10500 => 25536,
                    11000 => 26280,
                    11500 => 27024,
                    12000 => 27768,
                    12500 => 28512,
                    13000 => 29256,
                    13500 => 30000,
                    14000 => 30744,
                    14500 => 31488,
                    15000 => 32232,
                    15500 => 32976,
                    16000 => 33720,
                    16500 => 34464,
                    17000 => 35208,
                    17500 => 35952,
                    18000 => 36696,
                    18500 => 37440,
                    19000 => 38184,
                    19500 => 38928,
                    20000 => 39672,
                    20500 => 40416,
                    21000 => 41160,
                    21500 => 41904,
                    22000 => 42648,
                    22500 => 43392,
                    23000 => 44136,
                    23500 => 44880,
                    24000 => 45624,
                    24500 => 46368,
                    25000 => 47112,
                    25500 => 47856,
                    26000 => 48600,
                    26500 => 49344,
                    27000 => 50088,
                    27500 => 50832,
                    28000 => 51576,
                    28500 => 52320,
                    29000 => 53064,
                    29500 => 53808,
                    30000 => 54552,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 200,
            'maximumTotalCover' => 2500,
        ]);
    }

    protected static function getParcelforceGlobalpriorityRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '9_NON_EU' => [
                    500 => 5700,
                    1000 => 6018,
                    1500 => 6336,
                    2000 => 6654,
                    2500 => 6972,
                    3000 => 7242,
                    3500 => 7512,
                    4000 => 7782,
                    4500 => 8052,
                    5000 => 8322,
                    5500 => 8502,
                    6000 => 8682,
                    6500 => 8862,
                    7000 => 9042,
                    7500 => 9222,
                    8000 => 9402,
                    8500 => 9582,
                    9000 => 9762,
                    9500 => 9942,
                    10000 => 10122,
                    10500 => 10302,
                    11000 => 10482,
                    11500 => 10662,
                    12000 => 10842,
                    12500 => 11022,
                    13000 => 11202,
                    13500 => 11382,
                    14000 => 11562,
                    14500 => 11742,
                    15000 => 11922,
                    15500 => 12102,
                    16000 => 12282,
                    16500 => 12462,
                    17000 => 12642,
                    17500 => 12822,
                    18000 => 13002,
                    18500 => 13182,
                    19000 => 13362,
                    19500 => 13542,
                    20000 => 13722,
                    20500 => 13902,
                    21000 => 14082,
                    21500 => 14262,
                    22000 => 14442,
                    22500 => 14622,
                    23000 => 14802,
                    23500 => 14982,
                    24000 => 15162,
                    24500 => 15342,
                    25000 => 15522,
                    25500 => 15702,
                    26000 => 15882,
                    26500 => 16062,
                    27000 => 16242,
                    27500 => 16422,
                    28000 => 16602,
                    28500 => 16782,
                    29000 => 16962,
                    29500 => 17142,
                    30000 => 17322,
                ],
                '10' => [
                    500 => 5880,
                    1000 => 6420,
                    1500 => 6960,
                    2000 => 7500,
                    2500 => 8040,
                    3000 => 8538,
                    3500 => 9036,
                    4000 => 9534,
                    4500 => 10032,
                    5000 => 10530,
                    5500 => 10908,
                    6000 => 11286,
                    6500 => 11664,
                    7000 => 12042,
                    7500 => 12420,
                    8000 => 12798,
                    8500 => 13176,
                    9000 => 13554,
                    9500 => 13932,
                    10000 => 14310,
                    10500 => 14616,
                    11000 => 14922,
                    11500 => 15228,
                    12000 => 15534,
                    12500 => 15840,
                    13000 => 16146,
                    13500 => 16452,
                    14000 => 16758,
                    14500 => 17064,
                    15000 => 17370,
                    15500 => 17676,
                    16000 => 17982,
                    16500 => 18288,
                    17000 => 18594,
                    17500 => 18900,
                    18000 => 19206,
                    18500 => 19512,
                    19000 => 19818,
                    19500 => 20124,
                    20000 => 20430,
                    20500 => 20736,
                    21000 => 21042,
                    21500 => 21348,
                    22000 => 21654,
                    22500 => 21960,
                    23000 => 22266,
                    23500 => 22572,
                    24000 => 22878,
                    24500 => 23184,
                    25000 => 23490,
                    25500 => 23796,
                    26000 => 24102,
                    26500 => 24408,
                    27000 => 24714,
                    27500 => 25020,
                    28000 => 25326,
                    28500 => 25632,
                    29000 => 25938,
                    29500 => 26244,
                    30000 => 26550,
                ],
                '11' => [
                    500 => 6840,
                    1000 => 7476,
                    1500 => 8112,
                    2000 => 8748,
                    2500 => 9384,
                    3000 => 10002,
                    3500 => 10620,
                    4000 => 11238,
                    4500 => 11856,
                    5000 => 12474,
                    5500 => 13038,
                    6000 => 13602,
                    6500 => 14166,
                    7000 => 14730,
                    7500 => 15294,
                    8000 => 15858,
                    8500 => 16422,
                    9000 => 16986,
                    9500 => 17550,
                    10000 => 18114,
                    10500 => 18516,
                    11000 => 18918,
                    11500 => 19320,
                    12000 => 19722,
                    12500 => 20124,
                    13000 => 20526,
                    13500 => 20928,
                    14000 => 21330,
                    14500 => 21732,
                    15000 => 22134,
                    15500 => 22602,
                    16000 => 23070,
                    16500 => 23538,
                    17000 => 24006,
                    17500 => 24474,
                    18000 => 24942,
                    18500 => 25410,
                    19000 => 25878,
                    19500 => 26346,
                    20000 => 26814,
                    20500 => 27282,
                    21000 => 27750,
                    21500 => 28218,
                    22000 => 28686,
                    22500 => 29154,
                    23000 => 29622,
                    23500 => 30090,
                    24000 => 30558,
                    24500 => 31026,
                    25000 => 31494,
                    25500 => 31962,
                    26000 => 32430,
                    26500 => 32898,
                    27000 => 33366,
                    27500 => 33834,
                    28000 => 34302,
                    28500 => 34770,
                    29000 => 35238,
                    29500 => 35706,
                    30000 => 36174,
                ],
                '12' => [
                    500 => 7440,
                    1000 => 8280,
                    1500 => 9120,
                    2000 => 9960,
                    2500 => 10800,
                    3000 => 11640,
                    3500 => 12480,
                    4000 => 13320,
                    4500 => 14160,
                    5000 => 15000,
                    5500 => 15762,
                    6000 => 16524,
                    6500 => 17286,
                    7000 => 18048,
                    7500 => 18810,
                    8000 => 19572,
                    8500 => 20334,
                    9000 => 21096,
                    9500 => 21858,
                    10000 => 22620,
                    10500 => 23286,
                    11000 => 23952,
                    11500 => 24618,
                    12000 => 25284,
                    12500 => 25950,
                    13000 => 26616,
                    13500 => 27282,
                    14000 => 27948,
                    14500 => 28614,
                    15000 => 29280,
                    15500 => 29946,
                    16000 => 30612,
                    16500 => 31278,
                    17000 => 31944,
                    17500 => 32610,
                    18000 => 33276,
                    18500 => 33942,
                    19000 => 34608,
                    19500 => 35274,
                    20000 => 35940,
                    20500 => 36606,
                    21000 => 37272,
                    21500 => 37938,
                    22000 => 38604,
                    22500 => 39270,
                    23000 => 39936,
                    23500 => 40602,
                    24000 => 41268,
                    24500 => 41934,
                    25000 => 42600,
                    25500 => 43266,
                    26000 => 43932,
                    26500 => 44598,
                    27000 => 45264,
                    27500 => 45930,
                    28000 => 46596,
                    28500 => 47262,
                    29000 => 47928,
                    29500 => 48594,
                    30000 => 49260,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 100,
            'maximumTotalCover' => 2500,
        ]);
    }

    protected static function getParcelforceGlobalvalueRates($country): array
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2021' => [
                '10' => [
                    500 => 5586,
                    1000 => 6102,
                    1500 => 6618,
                    2000 => 7134,
                    2500 => 7650,
                    3000 => 8124,
                    3500 => 8598,
                    4000 => 9072,
                    4500 => 9546,
                    5000 => 10020,
                    5500 => 10380,
                    6000 => 10740,
                    6500 => 11100,
                    7000 => 11460,
                    7500 => 11820,
                    8000 => 12180,
                    8500 => 12540,
                    9000 => 12900,
                    9500 => 13260,
                    10000 => 13620,
                    10500 => 13914,
                    11000 => 14208,
                    11500 => 14502,
                    12000 => 14796,
                    12500 => 15090,
                    13000 => 15384,
                    13500 => 15678,
                    14000 => 15972,
                    14500 => 16266,
                    15000 => 16560,
                    15500 => 16854,
                    16000 => 17148,
                    16500 => 17442,
                    17000 => 17736,
                    17500 => 18030,
                    18000 => 18324,
                    18500 => 18618,
                    19000 => 18912,
                    19500 => 19206,
                    20000 => 19500,
                    20500 => 19794,
                    21000 => 20088,
                    21500 => 20382,
                    22000 => 20676,
                    22500 => 20970,
                    23000 => 21264,
                    23500 => 21558,
                    24000 => 21852,
                    24500 => 22146,
                    25000 => 22440,
                    25500 => 22734,
                    26000 => 23028,
                    26500 => 23322,
                    27000 => 23616,
                    27500 => 23910,
                    28000 => 24204,
                    28500 => 24498,
                    29000 => 24792,
                    29500 => 25086,
                    30000 => 25380,
                ],
                '11' => [
                    500 => 6498,
                    1000 => 7104,
                    1500 => 7710,
                    2000 => 8316,
                    2500 => 8922,
                    3000 => 9510,
                    3500 => 10098,
                    4000 => 10686,
                    4500 => 11274,
                    5000 => 11862,
                    5500 => 12402,
                    6000 => 12942,
                    6500 => 13482,
                    7000 => 14022,
                    7500 => 14562,
                    8000 => 15102,
                    8500 => 15642,
                    9000 => 16182,
                    9500 => 16722,
                    10000 => 17262,
                    10500 => 17646,
                    11000 => 18030,
                    11500 => 18414,
                    12000 => 18798,
                    12500 => 19182,
                    13000 => 19566,
                    13500 => 19950,
                    14000 => 20334,
                    14500 => 20718,
                    15000 => 21102,
                    15500 => 21552,
                    16000 => 22002,
                    16500 => 22452,
                    17000 => 22902,
                    17500 => 23352,
                    18000 => 23802,
                    18500 => 24252,
                    19000 => 24702,
                    19500 => 25152,
                    20000 => 25602,
                    20500 => 26052,
                    21000 => 26502,
                    21500 => 26952,
                    22000 => 27402,
                    22500 => 27852,
                    23000 => 28302,
                    23500 => 28752,
                    24000 => 29202,
                    24500 => 29652,
                    25000 => 30102,
                    25500 => 30552,
                    26000 => 31002,
                    26500 => 31452,
                    27000 => 31902,
                    27500 => 32352,
                    28000 => 32802,
                    28500 => 33252,
                    29000 => 33702,
                    29500 => 34152,
                    30000 => 34602,
                ],
                '12' => [
                    500 => 7068,
                    1000 => 7866,
                    1500 => 8664,
                    2000 => 9462,
                    2500 => 10260,
                    3000 => 11058,
                    3500 => 11856,
                    4000 => 12654,
                    4500 => 13452,
                    5000 => 14250,
                    5500 => 14976,
                    6000 => 15702,
                    6500 => 16428,
                    7000 => 17154,
                    7500 => 17880,
                    8000 => 18606,
                    8500 => 19332,
                    9000 => 20058,
                    9500 => 20784,
                    10000 => 21510,
                    10500 => 22146,
                    11000 => 22782,
                    11500 => 23418,
                    12000 => 24054,
                    12500 => 24690,
                    13000 => 25326,
                    13500 => 25962,
                    14000 => 26598,
                    14500 => 27234,
                    15000 => 27870,
                    15500 => 28506,
                    16000 => 29142,
                    16500 => 29778,
                    17000 => 30414,
                    17500 => 31050,
                    18000 => 31686,
                    18500 => 32322,
                    19000 => 32958,
                    19500 => 33594,
                    20000 => 34230,
                    20500 => 34866,
                    21000 => 35502,
                    21500 => 36138,
                    22000 => 36774,
                    22500 => 37410,
                    23000 => 38046,
                    23500 => 38682,
                    24000 => 39318,
                    24500 => 39954,
                    25000 => 40590,
                    25500 => 41226,
                    26000 => 41862,
                    26500 => 42498,
                    27000 => 43134,
                    27500 => 43770,
                    28000 => 44406,
                    28500 => 45042,
                    29000 => 45678,
                    29500 => 46314,
                    30000 => 46950,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country, [
            'maximumInclusiveCompensation' => 100,
            'maximumTotalCover' => 500,
        ]);
    }


    // Private Methods
    // =========================================================================

    private static function getAllEuropeanCountries()
    {
        return array_merge(self::$europeZone1, self::$europeZone2, self::$europeZone3);
    }

    private static function getZone($country): string
    {
        if ($country === 'GB') {
            return 'UK';
        } else if (in_array($country, self::$europeZone1)) {
            return 'EUR_1';
        } else if (in_array($country, self::$europeZone2)) {
            return 'EUR_2';
        } else if (in_array($country, self::$europeZone3)) {
            return 'EUR_3';
        } else if (in_array($country, self::$euro)) {
            return 'EU';
        }
        
        if (in_array($country, self::$worldZone2)) {
            return '2';
        }

        if (in_array($country, self::$worldZone3)) {
            return '3';
        }

        return '1';
    }

    private static function getParcelforceZone($country): string
    {
        if (in_array($country, ['JE', 'GG', 'IM'])) {
            return '4';
        }

        if ('IE' === $country) {
            return '5';
        }

        if (in_array($country, ['BE', 'NL', 'LU'])) {
            return '6';
        }

        if (in_array($country, ['FR', 'DE', 'DK'])) {
            return '7';
        }

        if (in_array($country, ['IT', 'ES', 'PT', 'GR'])) {
            return '8';
        }

        if (in_array($country, self::$euro)) {
            return '9';
        }

        if (in_array($country, self::$europeNonEu)) {
            return '9_NON_EU';
        }

        if (in_array($country, ['US', 'CA'])) {
            return '10';
        }

        if (in_array($country, self::$farEast)) {
            return '11';
        }

        if (in_array($country, self::$australasia)) {
            return '11';
        }

        return '12';
    }

    private static function getRateYear(): int|string|null
    {
        // Get the last item as default
        $currentYear = key(array_slice(self::$rateYears, -1, 1, true));

        foreach (self::$rateYears as $year => $start) {
            if (date('Y-m-d') > strtotime($start)) {
                $currentYear = $year;
            }
        }

        return $currentYear;
    }

    private static function getValueForYear($array)
    {
        // Get the pricing as applicable
        $year = self::getRateYear();

        // Is there a rate for this year?
        $value = $array[$year] ?? null;

        // Try and find any previous years
        if (!$value) {
            $value = end($array);
        }

        return $value;
    }

    private static function getBoxPricing($boxes, $bands, $maxCompensation = 0): array
    {
        // Get the pricing as applicable
        $pricingBand = self::getValueForYear($bands);

        $boxesWithPricing = [];

        // Get pricing for this year and for each box
        foreach ($boxes as $key => $box) {
            $prices = $pricingBand[$key] ?? $pricingBand['packet'] ?? [];

            // For ease-of-use, create multiple boxes for each weight
            foreach ($prices as $weight => $price) {
                $newKey = $key . '-' . $weight;
                $newBox = $box;
                $newBox['weight'] = $weight;
                $newBox['price'] = $price;

                // Check for max compensation
                if (self::$checkCompensation && $maxCompensation) {
                    if ($price > $maxCompensation) {
                        continue;
                    }
                }

                $boxesWithPricing[$newKey] = $newBox;
            }
        }

        return $boxesWithPricing;
    }

    private static function getInternationalBoxPricing($bands, $country, $maxCompensation = 0): array
    {
        $boxes = self::$internationalDefaultBox;

        // Prices will be in international format, so grab the right one.
        // Europe, Zone 1, Zone 2, Zone 3 (previously Zone 1)
        $boxPricing = self::getBoxPricing($boxes, $bands, $maxCompensation);
        $zone = self::getZone($country);

        foreach ($boxPricing as $key => &$box) {
            if ($zone === 'EUR_1') {
                $box['price'] = $box['price'][0];
            } else if ($zone === 'EUR_2') {
                $box['price'] = $box['price'][1];
            } else if ($zone === 'EUR_3' || $zone === 'EU') {
                $box['price'] = $box['price'][2];
            } else if ($zone === '1') {
                $box['price'] = $box['price'][3];
            } else if ($zone === '2') {
                $box['price'] = $box['price'][4];
            } else if ($zone === '3') {
                // Fallback to zone 1 for older prices.
                $box['price'] = $box['price'][5] ?? $box['price'][3];
            } else {
                // No price for this country
                unset($boxPricing[$key]);
            }
        }

        return $boxPricing;
    }

    private static function getParcelforceBoxPricing($bands, $country, $options = []): array
    {
        $boxesWithPricing = [];
        $maximumTotalCover = $options['maximumTotalCover'] ?? 0;
        $maximumInclusiveCompensation = $options['maximumInclusiveCompensation'] ?? 0;

        $zone = self::getParcelforceZone($country);

        // Get the pricing as applicable
        $pricingBand = self::getValueForYear($bands);

        // Get the pricing band for the zone
        $pricing = $pricingBand[$zone] ?? [];

        if (!$pricing) {
            return [];
        }

        $totalActualWeight = 0;
        $totalVolumetricWeight = 0;
        $totalValuedItems = self::$order->itemSubtotal;

        foreach (PostieHelper::getOrderLineItems(self::$order) as $item) {
            for ($i = 0; $i < $item->qty; $i++) {
                $totalActualWeight += $item->weight;
                $totalVolumetricWeight += self::getVolumetricWeight($item->length, $item->width, $item->height);
            }
        }

        $chargeableWeight = ($totalActualWeight > $totalVolumetricWeight) ? $totalActualWeight : $totalVolumetricWeight;

        foreach ($pricing as $maxWeight => $price) {
            if ($chargeableWeight <= $maxWeight) {
                // Don't return the quote if valued items is greater than maximum total
                // cover of the service.
                if ($maximumTotalCover > 0 && $totalValuedItems > $maximumTotalCover) {
                    return [];
                }

                // Additional compensation cost.
                $price += self::getAdditionalCompensationCost($totalValuedItems, $maximumInclusiveCompensation);

                // Rate includes VAT.
                if (!self::$includeVat) {
                    $price = $price / 1.2;
                }

                // There are no boxes, so make some large-ish ones. It's weight-based
                $key = 'Weighted-Box-' . $maxWeight;

                $boxesWithPricing[$key] = [
                    'length' => 1000,
                    'width' => 1000,
                    'height' => 1000,
                    'weight' => $maxWeight,
                    'price' => $price,
                ];
            }
        }

        return $boxesWithPricing;
    }

    private static function getVolumetricWeight($l, $w, $h): float|int
    {
        return ($l * $w * $h) / 5000;
    }

    private static function getAdditionalCompensationCost($valuedItem, $maximumInclusiveCompensation): float|int
    {
        // No compensation included for globaleconomy service and if it's under
        // max. inc. compensation there's no extra cost.
        if (!$maximumInclusiveCompensation || $valuedItem <= $maximumInclusiveCompensation) {
            return 0;
        }

        // £1.80 including VAT for the first extra £100 cover. The additional
        // cost is in pence since it will be added before converting back to £.
        $cost = 180;
        $extra = ($valuedItem - $maximumInclusiveCompensation) - 100;

        if (0 >= $extra) {
            return $cost;
        }

        // £4.50 including VAT for every subsequent £100. The additional cost
        // is in pence since it will be added before converting back to £.
        $cost += ceil($extra / 100) * 450;

        return $cost;
    }

}
