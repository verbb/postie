<?php
namespace verbb\postie\helpers;

use verbb\postie\models\Box;
use verbb\postie\models\Item;

use craft\helpers\ArrayHelper;
use craft\elements\Address;

use craft\commerce\Plugin as Commerce;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

use DVDoug\BoxPacker\Packer;

class TestingHelper
{
    // Properties
    // =========================================================================

    private static array $_addresses = [
        [
            'addressLine1' => '552 Victoria Street',
            'locality' => 'North Melbourne',
            'postalCode' => '3051',
            'administrativeArea' => 'VIC',
            'countryCode' => 'AU',
        ],
        [
            'addressLine1' => '83 Langride Street',
            'locality' => 'Collingwood',
            'postalCode' => '3066',
            'administrativeArea' => 'VIC',
            'countryCode' => 'AU',
        ],
        [
            'addressLine1' => '10-14 Cameron Street',
            'locality' => 'Launceston',
            'postalCode' => '7250',
            'administrativeArea' => 'TAS',
            'countryCode' => 'AU',
        ],
        [
            'addressLine1' => 'One Infinite Loop',
            'locality' => 'Cupertino',
            'postalCode' => '95014',
            'administrativeArea' => 'CA',
            'countryCode' => 'US',
        ],
        [
            'addressLine1' => '1600 Amphitheatre Parkway',
            'locality' => 'Mountain View',
            'postalCode' => '94043',
            'administrativeArea' => 'CA',
            'countryCode' => 'US',
        ],
        [
            'addressLine1' => '290 Bremner Blvd',
            'locality' => 'Toronto',
            'postalCode' => 'M5V 3L9',
            'administrativeArea' => 'ON',
            'countryCode' => 'CA',
        ],
        [
            'addressLine1' => '275 Notre-Dame St. East',
            'locality' => 'Montreal',
            'postalCode' => 'H2Y 1C6',
            'administrativeArea' => 'QC',
            'countryCode' => 'CA',
        ],
        [
            'addressLine1' => '109 Wakefield Street',
            'locality' => 'Wellington',
            'postalCode' => '6011',
            'countryCode' => 'NZ',
        ],
        [
            'addressLine1' => '86 Kilmore Street',
            'locality' => 'Christchurch',
            'postalCode' => '8013',
            'countryCode' => 'NZ',
        ],
        [
            'addressLine1' => '2 Bedfont Lane',
            'locality' => 'London',
            'postalCode' => 'CV226PD',
            'countryCode' => 'GB',
        ],
        [
            'addressLine1' => 'Southam Rd',
            'locality' => 'Dunchurch',
            'postalCode' => 'CV226PD',
            'countryCode' => 'GB',
        ],
        [
            'addressLine1' => '139 Main St',
            'locality' => 'Glasgow',
            'postalCode' => 'G73 2JJ',
            'countryCode' => 'GB',
        ],
        [
            'addressLine1' => 'Rådhusplassen 1',
            'locality' => 'Oslo',
            'postalCode' => '0037',
            'countryCode' => 'NO',
        ],
        [
            'addressLine1' => 'Neumanns gate 2a',
            'locality' => 'Bergen',
            'postalCode' => '5011',
            'countryCode' => 'NO',
        ],
        [
            'addressLine1' => 'Place de l‘Hôtel de Ville',
            'locality' => 'Paris',
            'postalCode' => '75004',
            'countryCode' => 'FR',
        ],
        [
            'addressLine1' => '5 Rue de l‘Hôtel de ville',
            'locality' => 'Nice',
            'postalCode' => '06000',
            'countryCode' => 'FR',
        ],
        [
            'addressLine1' => 'Dame St',
            'locality' => 'Dublin',
            'postalCode' => '8PVM+H5',
            'countryCode' => 'IR',
        ],
        [
            'addressLine1' => 'Coolsingel 40',
            'locality' => 'Rotterdam',
            'postalCode' => '3011 AD',
            'countryCode' => 'NL',
        ],
        [
            'addressLine1' => 'Nieuwezijds Voorburgwal 147',
            'locality' => 'Amsterdam',
            'postalCode' => '1012 RJ',
            'countryCode' => 'NL',
        ],
        [
            'addressLine1' => 'Kurfürstendamm 26',
            'locality' => 'Berlin',
            'postalCode' => '10719',
            'countryCode' => 'DE',
        ],
        [
            'addressLine1' => 'Rosenstraße 1',
            'locality' => 'München',
            'postalCode' => '80331',
            'countryCode' => 'DE',
        ],
    ];


    // Static Methods
    // =========================================================================

    public static function getTestAddress($countryCode, $criteria = [], $order = null): Address
    {
        $filter = array_merge(['countryCode' => $countryCode], $criteria);

        $addresses = ArrayHelper::whereMultiple(self::$_addresses, $filter);

        // Get the first address returned
        $address = ArrayHelper::firstValue($addresses) ?? [];

        // Set the owner of the address to be the order, if present
        if ($order) {
            $address['ownerId'] = $order->id;
        }

        // Create a new address element, even if empty
        return new Address($address);
    }

    public static function getTestPackedBoxes($dimensionUnit, $weightUnit, $qty1 = 1, $qty2 = 1): array
    {
        $packer = new Packer();

        $box = new Box();
        $box->setDimensions('Small Box', 30, 30, 10, 1000);
        $packer->addBox($box);

        $box = new Box();
        $box->setDimensions('Medium Box', 130, 130, 20, 11000);
        $packer->addBox($box);

        $box = new Box();
        $box->setDimensions('Large Box', 300, 300, 50, 10000);
        $packer->addBox($box);

        $item = new Item();
        $item->setDimensions('Test Item #1', 20, 20, 10, 500);
        $packer->addItem($item, $qty1);

        $item = new Item();
        $item->setDimensions('Test Item #2', 100, 100, 20, 200);
        $packer->addItem($item, $qty2);

        $packedBoxes = $packer->pack();

        $list = [];

        foreach ($packedBoxes as $packedBox) {
            // Convert back to the provider's units
            $list[] = [
                'width' => (new Length($packedBox->getBox()->getOuterWidth(), 'mm'))->toUnit($dimensionUnit),
                'length' => (new Length($packedBox->getBox()->getOuterLength(), 'mm'))->toUnit($dimensionUnit),
                'height' => (new Length($packedBox->getBox()->getOuterDepth(), 'mm'))->toUnit($dimensionUnit),
                'weight' => (new Mass($packedBox->getWeight(), 'g'))->toUnit($weightUnit),
            ];
        }

        return $list;
    }
}
