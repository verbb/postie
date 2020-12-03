<?php
namespace verbb\postie\inc\royalmail;

use Craft;
use craft\helpers\StringHelper;

class RoyalMailRates
{
    // Properties
    // =========================================================================

    private static $euro = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

    private static $europe = ['AL','AD', 'AM', 'AT', 'BY', 'BE', 'BA', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FO', 'FI', 'FR', 'GE', 'GI', 'GR', 'HU', 'HR', 'IE', 'IS', 'IT', 'LT', 'LU', 'LV', 'MC', 'MK', 'MT', 'NO', 'NL', 'PO', 'PT', 'RO', 'RU', 'SE', 'SI', 'SK', 'SM', 'TR', 'UA', 'VA'];

    private static $worldZone2 = ['AU', 'PW','IO', 'CX', 'CC', 'CK', 'FJ', 'PF', 'TF', 'KI', 'MO', 'NR', 'NC', 'NZ', 'PG', 'NU', 'NF', 'LA', 'PN', 'TO', 'TV', 'WS', 'AS', 'SG', 'SB', 'TK'];

    private static $worldZone3 = ['US'];

    private static $farEast = ['CN', 'HK', 'MO', 'JP', 'MN', 'KP', 'KR', 'TW', 'BN', 'KH', 'TL', 'ID', 'LA', 'MY', 'MM', 'PH', 'SG', 'TH', 'VN', 'RU'];

    private static $australasia = ['AU', 'PF', 'NU', 'TO', 'CX', 'KI', 'PG', 'TV', 'CC', 'NR', 'PN', 'VU', 'CK', 'NC', 'SB', 'WF', 'FJ', 'NZ', 'TK', 'WS'];

    protected static $rateYears = [
        '2019' => '2019-03-25',
        '2020' => '2020-03-23',
    ];


    // Public Methods
    // =========================================================================

    public static function getRates($country, $service)
    {
        $rates = [];

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

    public static function getFirstClassRates($country)
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    100 => 76,
                ],
                'large-letter' => [
                    100 => 115,
                    250 => 164,
                    500 => 214,
                    750 => 295,
                ],
                'small-parcel-wide' => [
                    1000 => 370,
                    2000 => 557,
                ],
                'small-parcel-deep' => [
                    1000 => 370,
                    2000 => 557,
                ],
                'small-parcel-bigger' => [
                    1000 => 370,
                    2000 => 557,
                ],
                'medium-parcel' => [
                    1000 => 590,
                    2000 => 902,
                    5000 => 1585,
                    10000 => 2190,
                    20000 => 3340,
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

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getFirstClassSignedRates($country)
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $boxPricing = self::getFirstClassRates($country);

        $signedForCost = self::getValueForYear([
            '2019' => 120,
            '2020' => 130,
        ]);

        $signedForPackageCost = self::getValueForYear([
            '2019' => 100,
        ]);

        foreach ($boxPricing as $key => &$box) {
            if (strstr($key, 'letter-')) {
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

    public static function getSecondClassRates($country)
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    100 => 65,
                ],
                'large-letter' => [
                    100 => 88,
                    250 => 140,
                    500 => 183,
                    750 => 248,
                ],
                'small-parcel-wide' => [
                    1000 => 310,
                    2000 => 310,
                ],
                'small-parcel-deep' => [
                    1000 => 310,
                    2000 => 310,
                ],
                'small-parcel-bigger' => [
                    1000 => 310,
                    2000 => 310,
                ],
                'medium-parcel' => [
                    1000 => 520,
                    2000 => 520,
                    5000 => 899,
                    10000 => 2025,
                    20000 => 2855,
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

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getSecondClassSignedRates($country)
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $boxPricing = self::getSecondClassRates($country);

        $signedForCost = self::getValueForYear([
            '2019' => 120,
            '2020' => 130,
        ]);

        $signedForPackageCost = self::getValueForYear([
            '2019' => 100,
        ]);

        foreach ($boxPricing as $key => &$box) {
            if (strstr($key, 'letter-')) {
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

    public static function getSpecialDelivery9amRates($country)
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
            '2020' => [
                'packet-50' => [
                    100 => 2024,
                    500 => 2289,
                    1000 => 2481,
                    2000 => 2970,
                ],
                'packet-1000' => [
                    100 => 2244,
                    500 => 2509,
                    1000 => 2701,
                    2000 => 3190,
                ],
                'packet-more' => [
                    100 => 2594,
                    500 => 2859,
                    1000 => 3051,
                    2000 => 3540,
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

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getSpecialDelivery1pmRates($country)
    {
        $zone = self::getZone($country);

        if ($zone !== 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'packet-500' => [
                    100 => 670,
                    500 => 750,
                    1000 => 880,
                    2000 => 1100,
                    10000 => 2660,
                    20000 => 4120,
                ],
                'packet-1000' => [
                    100 => 770,
                    500 => 850,
                    1000 => 980,
                    2000 => 1200,
                    10000 => 2760,
                    20000 => 4220,
                ],
                'packet-more' => [
                    100 => 970,
                    500 => 1050,
                    1000 => 1180,
                    2000 => 1400,
                    10000 => 2960,
                    20000 => 4420,
                ],
            ],
        ];

        $boxes = [
            'packet-500' => [
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

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceExpress9Rates($country)
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
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceExpress10Rates($country)
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
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceExpressAmRates($country)
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
        ];

        $boxes = [
            'packet-200' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceExpress24Rates($country)
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
        ];

        $boxes = [
            'packet-100' => [
                'length' => 1500,
                'width' => 750,
                'height' => 750,
                'weight' => 30000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceExpress48Rates($country)
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
        ];

        $boxes = [
            'packet-100' => [
                'length' => 2500,
                'width' => 1250,
                'height' => 1250,
                'weight' => 30000,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }


    public static function getInternationalStandardRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    10 => [ 145, 145, 145, 145 ],
                    20 => [ 145, 170, 170, 170 ],
                    100 => [ 170, 250, 255, 250 ],
                ],
                'large-letter' => [
                    100 => [ 300, 375, 425, 380 ],
                    250 => [ 425, 560, 665, 575 ],
                    500 => [ 510, 785, 965, 815 ],
                    750 => [ 610, 1045, 1330, 1090 ],
                ],
                'packet' => [
                    100 => [ 515, 625, 730, 838 ],
                    250 => [ 535, 735, 875, 984 ],
                    500 => [ 725, 1125, 1320, 1518 ],
                    750 => [ 850, 1400, 1645, 1773 ],
                    1000 => [ 965, 1680, 1980, 2118 ],
                    1250 => [ 1055, 1925, 2300, 2433 ],
                    1500 => [ 1160, 2150, 2610, 2698 ],
                    1750 => [ 1240, 2250, 2780, 2815 ],
                    2000 => [ 1285, 2395, 3000, 2971 ],
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

        return self::getInternationalBoxPricing($boxes, $bands, $country);
    }

    public static function getInternationalTrackedSignedRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = [ 'AX', 'AD', 'AR', 'AT', 'BB', 'BY', 'BE', 'BZ', 'BG', 'KH', 'CA', 'KY', 'CK', 'HR', 'CY', 'CZ', 'DK', 'EC', 'FO', 'FI', 'FR', 'GE', 'DE', 'GI', 'GR', 'GL', 'HK', 'HU', 'IS', 'ID', 'IE', 'IT', 'JP', 'LV', 'LB', 'LI', 'LT', 'LU', 'MY', 'MT', 'MD', 'NL', 'NZ', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS', 'SG', 'SK', 'SI', 'KR', 'ES', 'SE', 'CH', 'TH', 'TO', 'TT', 'TR', 'UG', 'AE', 'US', 'VA'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    10 => [ 645, 645, 645, 645 ],
                    20 => [ 645, 685, 685, 685 ],
                    100 => [ 690, 775, 780, 775 ],
                ],
                'large-letter' => [
                    100 => [ 850, 925, 980, 935 ],
                    250 => [ 895, 1035, 1140, 1055 ],
                    500 => [ 980, 1215, 1390, 1245 ],
                    750 => [ 1020, 1395, 1665, 1440 ],
                ],
                'packet' => [
                    100 => [ 985, 1110, 1200, 1288 ],
                    250 => [ 990, 1200, 1325, 1434 ],
                    500 => [ 1155, 1575, 1750, 1868 ],
                    750 => [ 1260, 1815, 2045, 2123 ],
                    1000 => [ 1355, 2080, 2370, 2468 ],
                    1250 => [ 1400, 2290, 2640, 2783 ],
                    1500 => [ 1475, 2430, 2890, 3048 ],
                    1750 => [ 1510, 2510, 3050, 3165 ],
                    2000 => [ 1535, 2600, 3215, 3321 ],
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

        return self::getInternationalBoxPricing($boxes, $bands, $country);
    }

    public static function getInternationalTrackedRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = [ 'AX', 'AD', 'AU', 'AT', 'BE', 'BR', 'CA', 'HR', 'CY', 'DK', 'EE', 'FO', 'FI', 'FR', 'DE', 'GI', 'GR', 'GL', 'HK', 'HU', 'IS', 'IN', 'IE', 'IL', 'IT', 'LV', 'LB', 'LI', 'LT', 'LU', 'MY', 'MT', 'NL', 'NZ', 'NO', 'PL', 'PT', 'RU', 'SM', 'RS', 'SG', 'SK', 'SI', 'KR', 'ES', 'SE', 'CH', 'TR', 'US', 'VA'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    10 => [ 774, 645, 645, 645, 645 ],
                    20 => [ 774, 645, 685, 685, 685 ],
                    100 => [ 828, 690, 775, 780, 775 ],
                ],
                'large-letter' => [
                    100 => [ 1020, 850, 925, 980, 935 ],
                    250 => [ 1074, 895, 1035, 1140, 1055 ],
                    500 => [ 1176, 980, 1215, 1390, 1245 ],
                    750 => [ 1224, 1020, 1395, 1665, 1440 ],
                ],
                'packet' => [
                    100 => [ 1182, 985, 1110, 1200, 1103 ],
                    250 => [ 1188, 990, 1200, 1325, 1234 ],
                    500 => [ 1386, 1155, 1575, 1750, 1668 ],
                    750 => [ 1512, 1260, 1815, 2045, 1923 ],
                    1000 => [ 1626, 1355, 2080, 2370, 2268 ],
                    1250 => [ 1680, 1400, 2290, 2640, 2583 ],
                    1500 => [ 1770, 1475, 2430, 2890, 2848 ],
                    1750 => [ 1812, 1510, 2510, 3050, 2965 ],
                    2000 => [ 1842, 1535, 2600, 3215, 3121 ],
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

        return self::getInternationalBoxPricing($boxes, $bands, $country);
    }

    public static function getInternationalSignedRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $included = [ 'AF', 'AL', 'DZ', 'AO', 'AI', 'AG', 'AM', 'AW', 'AU', 'AZ', 'BS', 'BH', 'BD', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BR', 'IO', 'VG', 'BN', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'CL', 'CN', 'CX', 'CO', 'KM', 'CG', 'CD', 'CR', 'CU', 'CW', 'DJ', 'DM', 'DO', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FJ', 'GF', 'PF', 'TF', 'GA', 'GM', 'GH', 'GD', 'GP', 'GT', 'GN', 'GW', 'GY', 'HT', 'HN', 'IN', 'IR', 'IQ', 'IL', 'CI', 'JM', 'JO', 'KZ', 'KE', 'KI', 'KW', 'KG', 'LA', 'LS', 'LR', 'LY', 'MO', 'MK', 'MG', 'YT', 'MW', 'MV', 'ML', 'MQ', 'MR', 'MU', 'MX', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NC', 'NI', 'NE', 'NG', 'NU', 'KP', 'NO', 'OM', 'PK', 'PW', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PR', 'QA', 'RE', 'RW', 'ST', 'SA', 'SN', 'SC', 'SL', 'SB', 'ZA', 'SS', 'LK', 'BQ', 'SH', 'KN', 'LC', 'MF', 'SX', 'VC', 'SD', 'SR', 'SZ', 'SY', 'TW', 'TJ', 'TZ', 'TL', 'TG', 'TK', 'TN', 'TM', 'TC', 'TV', 'UA', 'UY', 'UZ', 'VU', 'VE', 'VN', 'WF', 'EH', 'WS', 'YE', 'ZM', 'ZW'];

        if (!in_array($country, $included)) {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    10 => [ 645, 645, 645 ],
                    20 => [ 645, 685, 685 ],
                    100 => [ 690, 775, 780 ],
                ],
                'large-letter' => [
                    100 => [ 850, 925, 980 ],
                    250 => [ 895, 1035, 1140 ],
                    500 => [ 980, 1215, 1390 ],
                    750 => [ 1020, 1395, 1665 ],
                ],
                'packet' => [
                    100 => [ 985, 1110, 1200 ],
                    250 => [ 990, 1200, 1325 ],
                    500 => [ 1155, 1575, 1750 ],
                    750 => [ 1260, 1815, 2045 ],
                    1000 => [ 1355, 2080, 2370 ],
                    1250 => [ 1400, 2290, 2640 ],
                    1500 => [ 1475, 2430, 2890 ],
                    1750 => [ 1510, 2510, 3050 ],
                    2000 => [ 1535, 2600, 3215 ],
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

        return self::getInternationalBoxPricing($boxes, $bands, $country);
    }

    public static function getInternationalEconomyRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2020' => [
                'letter' => [
                    10 => 126,
                    20 => 126,
                    100 => 158,
                ],
                'large-letter' => [
                    100 => 292,
                    250 => 419,
                    500 => 482,
                    750 => 578,
                ],
                'packet' => [
                    100 => 500,
                    250 => 520,
                    500 => 710,
                    750 => 835,
                    1000 => 955,
                    1250 => 1045,
                    1500 => 1150,
                    1750 => 1230,
                    2000 => 1275,
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
            'long-parcel' => [
                'length' => 600,
                'width' => 150,
                'height' => 150,
                'weight' => 500,
            ],
            'square-parcel' => [
                'length' => 300,
                'width' => 300,
                'height' => 300,
                'weight' => 500,
            ],
            'parcel' => [
                'length' => 450,
                'width' => 225,
                'height' => 225,
                'weight' => 500,
            ],
        ];

        return self::getBoxPricing($boxes, $bands);
    }

    public static function getParcelforceIrelandexpressRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2019' => [
                '5' => [
                    500 => 1668,
                    1000 => 1668,
                    1500 => 1668,
                    2000 => 1668,
                    2500 => 1770,
                    3000 => 1770,
                    3500 => 1770,
                    4000 => 1770,
                    4500 => 1770,
                    5000 => 1770,
                    5500 => 2118,
                    6000 => 2118,
                    6500 => 2118,
                    7000 => 2118,
                    7500 => 2118,
                    8000 => 2118,
                    8500 => 2118,
                    9000 => 2118,
                    9500 => 2118,
                    10000 => 2118,
                    15000 => 2764,
                    20000 => 3301,
                    25000 => 4414,
                    30000 => 4828,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country);
    }

    public static function getParcelforceGlobaleconomyRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2019' => [
                '10' => [
                    500 => 2470,
                    1000 => 2725,
                    1500 => 2980,
                    2000 => 3235,
                    2500 => 3880,
                    3000 => 4525,
                    3500 => 5170,
                    4000 => 5815,
                    4500 => 6460,
                    5000 => 7105,
                    5500 => 7475,
                    6000 => 7845,
                    6500 => 8215,
                    7000 => 8585,
                    7500 => 8955,
                    8000 => 9325,
                    8500 => 9695,
                    9000 => 10065,
                    9500 => 10435,
                    10000 => 10805,
                    15000 => 13205,
                    20000 => 15555,
                    25000 => 17905,
                    30000 => 20255,
                ],
                '11' => [
                    500 => 3185,
                    1000 => 3810,
                    1500 => 4435,
                    2000 => 5060,
                    2500 => 5670,
                    3000 => 6280,
                    3500 => 6890,
                    4000 => 7500,
                    4500 => 8110,
                    5000 => 8720,
                    5500 => 9175,
                    6000 => 9630,
                    6500 => 10085,
                    7000 => 10540,
                    7500 => 10995,
                    8000 => 11450,
                    8500 => 11905,
                    9000 => 12360,
                    9500 => 12815,
                    10000 => 13270,
                    15000 => 16620,
                    20000 => 19970,
                    25000 => 23320,
                    30000 => 26670,
                ],
                '12' => [
                    500 => 3335,
                    1000 => 4050,
                    1500 => 4765,
                    2000 => 5480,
                    2500 => 6195,
                    3000 => 6910,
                    3500 => 7625,
                    4000 => 8340,
                    4500 => 9055,
                    5000 => 9770,
                    5500 => 10420,
                    6000 => 11070,
                    6500 => 11720,
                    7000 => 12370,
                    7500 => 13020,
                    8000 => 13670,
                    8500 => 14320,
                    9000 => 14970,
                    9500 => 15620,
                    10000 => 16270,
                    15000 => 20720,
                    20000 => 25220,
                    25000 => 29720,
                    30000 => 34220,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country);
    }

    public static function getParcelforceGlobalexpressRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2019' => [
                '4' => [
                    500 => 5250,
                    1000 => 5508,
                    1500 => 5766,
                    2000 => 6024,
                    2500 => 6276,
                    3000 => 6528,
                    3500 => 6780,
                    4000 => 7032,
                    4500 => 7284,
                    5000 => 7536,
                    5500 => 7818,
                    6000 => 8100,
                    6500 => 8382,
                    7000 => 8664,
                    7500 => 8946,
                    8000 => 9228,
                    8500 => 9510,
                    9000 => 9792,
                    9500 => 10074,
                    10000 => 10356,
                    15000 => 12516,
                    20000 => 14856,
                    25000 => 17196,
                    30000 => 19536,
                ],
                '5' => [
                    500 => 4794,
                    1000 => 5046,
                    1500 => 5298,
                    2000 => 5550,
                    2500 => 5922,
                    3000 => 6294,
                    3500 => 6666,
                    4000 => 7038,
                    4500 => 7410,
                    5000 => 7782,
                    5500 => 7956,
                    6000 => 8130,
                    6500 => 8304,
                    7000 => 8478,
                    7500 => 8652,
                    8000 => 8826,
                    8500 => 9000,
                    9000 => 9174,
                    9500 => 9348,
                    10000 => 9522,
                    15000 => 11862,
                    20000 => 13602,
                    25000 => 15342,
                    30000 => 17082,
                ],
                '6' => [
                    500 => 4350,
                    1000 => 4758,
                    1500 => 5166,
                    2000 => 5574,
                    2500 => 5910,
                    3000 => 6246,
                    3500 => 6582,
                    4000 => 6918,
                    4500 => 7254,
                    5000 => 7590,
                    5500 => 7800,
                    6000 => 8010,
                    6500 => 8220,
                    7000 => 8430,
                    7500 => 8640,
                    8000 => 8850,
                    8500 => 9060,
                    9000 => 9270,
                    9500 => 9480,
                    10000 => 9690,
                    15000 => 11610,
                    20000 => 13350,
                    25000 => 15090,
                    30000 => 16830,
                ],
                '7' => [
                    500 => 4476,
                    1000 => 4836,
                    1500 => 5196,
                    2000 => 5556,
                    2500 => 5976,
                    3000 => 6396,
                    3500 => 6816,
                    4000 => 7236,
                    4500 => 7656,
                    5000 => 8076,
                    5500 => 8394,
                    6000 => 8712,
                    6500 => 9030,
                    7000 => 9348,
                    7500 => 9666,
                    8000 => 9984,
                    8500 => 10302,
                    9000 => 10620,
                    9500 => 10938,
                    10000 => 11256,
                    15000 => 13716,
                    20000 => 15756,
                    25000 => 17796,
                    30000 => 19836,
                ],
                '8' => [
                    500 => 4806,
                    1000 => 5190,
                    1500 => 5574,
                    2000 => 5958,
                    2500 => 6270,
                    3000 => 6582,
                    3500 => 6894,
                    4000 => 7206,
                    4500 => 7518,
                    5000 => 7830,
                    5500 => 8142,
                    6000 => 8454,
                    6500 => 8766,
                    7000 => 9078,
                    7500 => 9390,
                    8000 => 9702,
                    8500 => 10014,
                    9000 => 10326,
                    9500 => 10638,
                    10000 => 10950,
                    15000 => 14010,
                    20000 => 16770,
                    25000 => 19530,
                    30000 => 22290,
                ],
                '9' => [
                    500 => 5250,
                    1000 => 5718,
                    1500 => 6186,
                    2000 => 6654,
                    2500 => 7404,
                    3000 => 8154,
                    3500 => 8904,
                    4000 => 9654,
                    4500 => 10404,
                    5000 => 11154,
                    5500 => 11658,
                    6000 => 12162,
                    6500 => 12666,
                    7000 => 13170,
                    7500 => 13674,
                    8000 => 14178,
                    8500 => 14682,
                    9000 => 15186,
                    9500 => 15690,
                    10000 => 16194,
                    15000 => 19554,
                    20000 => 23274,
                    25000 => 26994,
                    30000 => 30714,
                ],
                '10' => [
                    500 => 5255,
                    1000 => 5755,
                    1500 => 6255,
                    2000 => 6755,
                    2500 => 7155,
                    3000 => 7555,
                    3500 => 7955,
                    4000 => 8355,
                    4500 => 8755,
                    5000 => 9155,
                    5500 => 9580,
                    6000 => 10005,
                    6500 => 10430,
                    7000 => 10855,
                    7500 => 11280,
                    8000 => 11705,
                    8500 => 12130,
                    9000 => 12555,
                    9500 => 12980,
                    10000 => 13405,
                    15000 => 16655,
                    20000 => 19855,
                    25000 => 23055,
                    30000 => 26255,
                ],
                '11' => [
                    500 => 6370,
                    1000 => 7030,
                    1500 => 7690,
                    2000 => 8350,
                    2500 => 8925,
                    3000 => 9500,
                    3500 => 10075,
                    4000 => 10650,
                    4500 => 11225,
                    5000 => 11800,
                    5500 => 12270,
                    6000 => 12740,
                    6500 => 13210,
                    7000 => 13680,
                    7500 => 14150,
                    8000 => 14620,
                    8500 => 15090,
                    9000 => 15560,
                    9500 => 16030,
                    10000 => 16500,
                    15000 => 21650,
                    20000 => 26700,
                    25000 => 31750,
                    30000 => 36800,
                ],
                '12' => [
                    500 => 7270,
                    1000 => 8145,
                    1500 => 9020,
                    2000 => 9895,
                    2500 => 10705,
                    3000 => 11515,
                    3500 => 12325,
                    4000 => 13135,
                    4500 => 13945,
                    5000 => 14755,
                    5500 => 15385,
                    6000 => 16015,
                    6500 => 16645,
                    7000 => 17275,
                    7500 => 17905,
                    8000 => 18535,
                    8500 => 19165,
                    9000 => 19795,
                    9500 => 20425,
                    10000 => 21055,
                    15000 => 27705,
                    20000 => 34455,
                    25000 => 41205,
                    30000 => 47955,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country);
    }

    public static function getParcelforceGlobalpriorityRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2019' => [
                '4' => [
                    500 => 3000,
                    1000 => 3132,
                    1500 => 3264,
                    2000 => 3396,
                    2500 => 3498,
                    3000 => 3600,
                    3500 => 3702,
                    4000 => 3804,
                    4500 => 3906,
                    5000 => 4008,
                    5500 => 4050,
                    6000 => 4092,
                    6500 => 4134,
                    7000 => 4176,
                    7500 => 4218,
                    8000 => 4260,
                    8500 => 4302,
                    9000 => 4344,
                    9500 => 4386,
                    10000 => 4428,
                    15000 => 4908,
                    20000 => 5208,
                    25000 => 5508,
                    30000 => 5808,
                ],
                '5' => [
                    500 => 3420,
                    1000 => 3654,
                    1500 => 3888,
                    2000 => 4122,
                    2500 => 4350,
                    3000 => 4578,
                    3500 => 4806,
                    4000 => 5034,
                    4500 => 5262,
                    5000 => 5490,
                    5500 => 5592,
                    6000 => 5694,
                    6500 => 5796,
                    7000 => 5898,
                    7500 => 6000,
                    8000 => 6102,
                    8500 => 6204,
                    9000 => 6306,
                    9500 => 6408,
                    10000 => 6510,
                    15000 => 7320,
                    20000 => 8100,
                    25000 => 8880,
                    30000 => 9660,
                ],
                '6' => [
                    500 => 3078,
                    1000 => 3336,
                    1500 => 3594,
                    2000 => 3852,
                    2500 => 4110,
                    3000 => 4368,
                    3500 => 4626,
                    4000 => 4884,
                    4500 => 5142,
                    5000 => 5400,
                    5500 => 5514,
                    6000 => 5628,
                    6500 => 5742,
                    7000 => 5856,
                    7500 => 5970,
                    8000 => 6084,
                    8500 => 6198,
                    9000 => 6312,
                    9500 => 6426,
                    10000 => 6540,
                    15000 => 8010,
                    20000 => 8730,
                    25000 => 9450,
                    30000 => 10170,
                ],
                '7' => [
                    500 => 3582,
                    1000 => 3780,
                    1500 => 3978,
                    2000 => 4176,
                    2500 => 4392,
                    3000 => 4608,
                    3500 => 4824,
                    4000 => 5040,
                    4500 => 5256,
                    5000 => 5472,
                    5500 => 5628,
                    6000 => 5784,
                    6500 => 5940,
                    7000 => 6096,
                    7500 => 6252,
                    8000 => 6408,
                    8500 => 6564,
                    9000 => 6720,
                    9500 => 6876,
                    10000 => 7032,
                    15000 => 8532,
                    20000 => 9192,
                    25000 => 9852,
                    30000 => 10512,
                ],
                '8' => [
                    500 => 3894,
                    1000 => 4110,
                    1500 => 4326,
                    2000 => 4542,
                    2500 => 4776,
                    3000 => 5010,
                    3500 => 5244,
                    4000 => 5478,
                    4500 => 5712,
                    5000 => 5946,
                    5500 => 6138,
                    6000 => 6330,
                    6500 => 6522,
                    7000 => 6714,
                    7500 => 6906,
                    8000 => 7098,
                    8500 => 7290,
                    9000 => 7482,
                    9500 => 7674,
                    10000 => 7866,
                    15000 => 10086,
                    20000 => 10806,
                    25000 => 11526,
                    30000 => 12246,
                ],
                '9' => [
                    500 => 4218,
                    1000 => 4542,
                    1500 => 4866,
                    2000 => 5190,
                    2500 => 5466,
                    3000 => 5742,
                    3500 => 6018,
                    4000 => 6294,
                    4500 => 6570,
                    5000 => 6846,
                    5500 => 7080,
                    6000 => 7314,
                    6500 => 7548,
                    7000 => 7782,
                    7500 => 8016,
                    8000 => 8250,
                    8500 => 8484,
                    9000 => 8718,
                    9500 => 8952,
                    10000 => 9186,
                    15000 => 10446,
                    20000 => 11706,
                    25000 => 12966,
                    30000 => 14226,
                ],
                '10' => [
                    500 => 4480,
                    1000 => 4735,
                    1500 => 4990,
                    2000 => 5245,
                    2500 => 5895,
                    3000 => 6545,
                    3500 => 7195,
                    4000 => 7845,
                    4500 => 8495,
                    5000 => 9145,
                    5500 => 9520,
                    6000 => 9895,
                    6500 => 10270,
                    7000 => 10645,
                    7500 => 11020,
                    8000 => 11395,
                    8500 => 11770,
                    9000 => 12145,
                    9500 => 12520,
                    10000 => 12895,
                    15000 => 15295,
                    20000 => 17695,
                    25000 => 20095,
                    30000 => 22495,
                ],
                '11' => [
                    500 => 4750,
                    1000 => 5350,
                    1500 => 5950,
                    2000 => 6550,
                    2500 => 7150,
                    3000 => 7750,
                    3500 => 8350,
                    4000 => 8950,
                    4500 => 9550,
                    5000 => 10150,
                    5500 => 10605,
                    6000 => 11060,
                    6500 => 11515,
                    7000 => 11970,
                    7500 => 12425,
                    8000 => 12880,
                    8500 => 13335,
                    9000 => 13790,
                    9500 => 14245,
                    10000 => 14700,
                    15000 => 18150,
                    20000 => 21500,
                    25000 => 24850,
                    30000 => 28200,
                ],
                '12' => [
                    500 => 6015,
                    1000 => 6710,
                    1500 => 7405,
                    2000 => 8100,
                    2500 => 8790,
                    3000 => 9480,
                    3500 => 10170,
                    4000 => 10860,
                    4500 => 11550,
                    5000 => 12240,
                    5500 => 12860,
                    6000 => 13480,
                    6500 => 14100,
                    7000 => 14720,
                    7500 => 15340,
                    8000 => 15960,
                    8500 => 16580,
                    9000 => 17200,
                    9500 => 17820,
                    10000 => 18440,
                    15000 => 22890,
                    20000 => 27390,
                    25000 => 31890,
                    30000 => 36390,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country);
    }

    public static function getParcelforceGlobalvalueRates($country)
    {
        $zone = self::getZone($country);

        if ($zone === 'UK') {
            return [];
        }

        $bands = [
            '2019' => [
                '4' => [
                    500 => 978,
                    1000 => 1104,
                    1500 => 1230,
                    2000 => 1356,
                    2500 => 1452,
                    3000 => 1548,
                    3500 => 1644,
                    4000 => 1740,
                    4500 => 1836,
                    5000 => 1932,
                    5500 => 1974,
                    6000 => 2016,
                    6500 => 2058,
                    7000 => 2100,
                    7500 => 2142,
                    8000 => 2184,
                    8500 => 2226,
                    9000 => 2268,
                    9500 => 2310,
                    10000 => 2352,
                    15000 => 2652,
                    20000 => 2952,
                    25000 => 3252,
                    30000 => 3552,
                ],
                '5' => [
                    500 => 1686,
                    1000 => 1932,
                    1500 => 2178,
                    2000 => 2424,
                    2500 => 2670,
                    3000 => 2916,
                    3500 => 3162,
                    4000 => 3408,
                    4500 => 3654,
                    5000 => 3900,
                    5500 => 4008,
                    6000 => 4116,
                    6500 => 4224,
                    7000 => 4332,
                    7500 => 4440,
                    8000 => 4548,
                    8500 => 4656,
                    9000 => 4764,
                    9500 => 4872,
                    10000 => 4980,
                    15000 => 5700,
                    20000 => 6420,
                    25000 => 7140,
                    30000 => 7860,
                ],
                '6' => [
                    500 => 2268,
                    1000 => 2502,
                    1500 => 2736,
                    2000 => 2970,
                    2500 => 3192,
                    3000 => 3414,
                    3500 => 3636,
                    4000 => 3858,
                    4500 => 4080,
                    5000 => 4302,
                    5500 => 4404,
                    6000 => 4506,
                    6500 => 4608,
                    7000 => 4710,
                    7500 => 4812,
                    8000 => 4914,
                    8500 => 5016,
                    9000 => 5118,
                    9500 => 5220,
                    10000 => 5322,
                    15000 => 6042,
                    20000 => 6762,
                    25000 => 7482,
                    30000 => 8202,
                ],
                '7' => [
                    500 => 2058,
                    1000 => 2124,
                    1500 => 2190,
                    2000 => 2256,
                    2500 => 2514,
                    3000 => 2772,
                    3500 => 3030,
                    4000 => 3288,
                    4500 => 3546,
                    5000 => 3804,
                    5500 => 4008,
                    6000 => 4212,
                    6500 => 4416,
                    7000 => 4620,
                    7500 => 4824,
                    8000 => 5028,
                    8500 => 5232,
                    9000 => 5436,
                    9500 => 5640,
                    10000 => 5844,
                    15000 => 6444,
                    20000 => 7104,
                    25000 => 7764,
                    30000 => 8424,
                ],
                '8' => [
                    500 => 2592,
                    1000 => 2826,
                    1500 => 3060,
                    2000 => 3294,
                    2500 => 3498,
                    3000 => 3702,
                    3500 => 3906,
                    4000 => 4110,
                    4500 => 4314,
                    5000 => 4518,
                    5500 => 4680,
                    6000 => 4842,
                    6500 => 5004,
                    7000 => 5166,
                    7500 => 5328,
                    8000 => 5490,
                    8500 => 5652,
                    9000 => 5814,
                    9500 => 5976,
                    10000 => 6138,
                    15000 => 6858,
                    20000 => 7578,
                    25000 => 8298,
                    30000 => 9018,
                ],
                '9' => [
                    500 => 2712,
                    1000 => 3042,
                    1500 => 3372,
                    2000 => 3702,
                    2500 => 3972,
                    3000 => 4242,
                    3500 => 4512,
                    4000 => 4782,
                    4500 => 5052,
                    5000 => 5322,
                    5500 => 5550,
                    6000 => 5778,
                    6500 => 6006,
                    7000 => 6234,
                    7500 => 6462,
                    8000 => 6690,
                    8500 => 6918,
                    9000 => 7146,
                    9500 => 7374,
                    10000 => 7602,
                    15000 => 8862,
                    20000 => 10122,
                    25000 => 11382,
                    30000 => 12642,
                ],
                '10' => [
                    500 => 2770,
                    1000 => 3025,
                    1500 => 3280,
                    2000 => 3535,
                    2500 => 4180,
                    3000 => 4825,
                    3500 => 5470,
                    4000 => 6115,
                    4500 => 6760,
                    5000 => 7405,
                    5500 => 7775,
                    6000 => 8145,
                    6500 => 8515,
                    7000 => 8885,
                    7500 => 9255,
                    8000 => 9625,
                    8500 => 9995,
                    9000 => 10365,
                    9500 => 10735,
                    10000 => 11105,
                    15000 => 13505,
                    20000 => 15855,
                    25000 => 18205,
                    30000 => 20555,
                ],
                '11' => [
                    500 => 3455,
                    1000 => 4080,
                    1500 => 4705,
                    2000 => 5330,
                    2500 => 5940,
                    3000 => 6550,
                    3500 => 7160,
                    4000 => 7770,
                    4500 => 8380,
                    5000 => 8990,
                    5500 => 9445,
                    6000 => 9900,
                    6500 => 10355,
                    7000 => 10810,
                    7500 => 11265,
                    8000 => 11720,
                    8500 => 12175,
                    9000 => 12630,
                    9500 => 13085,
                    10000 => 13540,
                    15000 => 16890,
                    20000 => 20240,
                    25000 => 23590,
                    30000 => 26940,
                ],
                '12' => [
                    500 => 3575,
                    1000 => 4290,
                    1500 => 5005,
                    2000 => 5720,
                    2500 => 6435,
                    3000 => 7150,
                    3500 => 7865,
                    4000 => 8580,
                    4500 => 9295,
                    5000 => 10010,
                    5500 => 10660,
                    6000 => 11310,
                    6500 => 11960,
                    7000 => 12610,
                    7500 => 13260,
                    8000 => 13910,
                    8500 => 14560,
                    9000 => 15210,
                    9500 => 15860,
                    10000 => 16510,
                    15000 => 20960,
                    20000 => 25460,
                    25000 => 29960,
                    30000 => 34460,
                ],
            ],
        ];

        return self::getParcelforceBoxPricing($bands, $country);
    }


    // Private Methods
    // =========================================================================

    private static function getZone($country)
    {
        if ($country === 'GB') {
            return 'UK';
        } else if (in_array($country, self::$euro)) {
            return 'EU';
        } else if (in_array($country, self::$europe)) {
            return 'EUR';
        } else if (in_array($country, self::$worldZone2)) {
            return '2';
        } else if (in_array($country, self::$worldZone3)) {
            return '3';
        }

        return '1';
    }

    private static function getParcelforceZone($country)
    {
        if (in_array($country, ['JE', 'GG', 'IM'])) {
            return '4';
        } else if ('IR' === $country) {
            return '5';
        } else if (in_array($country, ['BE', 'NL', 'LU'])) {
            return '6';
        } else if (in_array($country, ['FR', 'DE', 'DK'])) {
            return '7';
        } else if (in_array($country, ['IT', 'ES', 'PT', 'GR'])) {
            return '8';
        } else if (in_array($country, self::$europe)) {
            return '9';
        } else if (in_array($country, ['US', 'CA'])) {
            return '10';
        } else if (in_array($country, self::$farEast)) {
            return '11';
        } else if (in_array($country, self::$australasia)) {
            return '11';
        }

        return '12';
    }

    private static function getRateYear()
    {
        // Get the last item as default
        $currentYear = key(array_slice(self::$rateYears, -1, 1, true));

        foreach (self::$rateYears as $year => $start ) {
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

    private static function getBoxPricing($boxes, $bands)
    {
        // Get the pricing as applicable
        $pricingBand = self::getValueForYear($bands);

        $boxesWithPricing = [];

        // Get pricing for this year and for each box
        foreach ($boxes as $key => $box) {
            $prices = $pricingBand[$key] ?? [];

            // For ease-of-use, create multiple boxes for each weight
            foreach ($prices as $weight => $price) {
                $newKey = $key . '-' . $weight;
                $newBox = $box;
                $newBox['weight'] = $weight;
                $newBox['price'] = $price;

                $boxesWithPricing[$newKey] = $newBox;
            }
        }

        return $boxesWithPricing;
    }

    private static function getInternationalBoxPricing($boxes, $bands, $country)
    {
        // Prices will be in international format, so grab the right one.
        // Europe, Zone 1, Zone 2, Zone 3 (previously Zone 1)
        $boxPricing = self::getBoxPricing($boxes, $bands);

        $zone = self::getZone($country);

        foreach ($boxPricing as $key => &$box) {
            if ($zone === 'EU') {
                $box['price'] = $box['price'][0];
            } else if ($zone === 'EUR') {
                $box['price'] = $box['price'][1];
            } else if ($zone === '1') {
                $box['price'] = $box['price'][2];
            } else if ($zone === '2') {
                $box['price'] = $box['price'][3] ?? $box['price'][2];
            } else if ($zone === '3') {
                $box['price'] = $box['price'][4] ?? $box['price'][2];
            } else {
                // No price for this country
                unset($boxPricing[$key]);
            }
        }

        return $boxPricing;
    }

    private static function getParcelforceBoxPricing($bands, $country)
    {
        $zone = self::getParcelforceZone($country);
        
        // Get the pricing as applicable
        $pricingBand = self::getValueForYear($bands);

        // Get the pricing band for the zone
        $pricing = $pricingBand[$zone] ?? [];

        $boxesWithPricing = [];

        foreach ($pricing as $weight => $price) {
            // There are no boxes, so make some large-ish ones. It's weight-based
            $key = 'Weighted-Box-' . $weight;

            $boxesWithPricing[$key] = [
                'length' => 1000,
                'width' => 1000,
                'height' => 1000,
                'weight' => $weight,
                'price' => $price,
            ];
        }

        return $boxesWithPricing;
    }

}
