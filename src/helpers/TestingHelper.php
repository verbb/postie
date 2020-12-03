<?php
namespace verbb\postie\helpers;

use verbb\postie\models\Box;
use verbb\postie\models\Item;

use craft\helpers\ArrayHelper;
use craft\commerce\models\Address;

use craft\commerce\Plugin as Commerce;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

use DVDoug\BoxPacker\Packer;

class TestingHelper
{
    // Properties
    // =========================================================================

    private static $_addresses = [
        [
            'address1' => '552 Victoria Street',
            'city' => 'North Melbourne',
            'zipCode' => '3051',
            'state' => 'VIC',
            'country' => 'AU',
        ],
        [
            'address1' => '83 Langride Street',
            'city' => 'Collingwood',
            'zipCode' => '3066',
            'state' => 'VIC',
            'country' => 'AU',
        ],
        [
            'address1' => '10-14 Cameron Street',
            'city' => 'Launceston',
            'zipCode' => '7250',
            'state' => 'TAS',
            'country' => 'AU',
        ],
        [
            'address1' => 'One Infinite Loop',
            'city' => 'Cupertino',
            'zipCode' => '95014',
            'state' => 'CA',
            'country' => 'US',
        ],
        [
            'address1' => '1600 Amphitheatre Parkway',
            'city' => 'Mountain View',
            'zipCode' => '94043',
            'state' => 'CA',
            'country' => 'US',
        ],
        [
            'address1' => '290 Bremner Blvd',
            'city' => 'Toronto',
            'zipCode' => 'M5V 3L9',
            'state' => 'ON',
            'country' => 'CA',
        ],
        [
            'address1' => '275 Notre-Dame St. East',
            'city' => 'Montreal',
            'zipCode' => 'H2Y 1C6',
            'state' => 'QC',
            'country' => 'CA',
        ],
        [
            'address1' => '109 Wakefield Street',
            'city' => 'Wellington',
            'zipCode' => '6011',
            'country' => 'NZ',
        ],
        [
            'address1' => '86 Kilmore Street',
            'city' => 'Christchurch',
            'zipCode' => '8013',
            'country' => 'NZ',
        ],
        [
            'address1' => '2 Bedfont Lane',
            'city' => 'London',
            'zipCode' => 'CV226PD',
            'country' => 'GB',
        ],
        [
            'address1' => '139 Main St',
            'city' => 'Glasgow',
            'zipCode' => 'G73 2JJ',
            'country' => 'GB',
        ],
        [
            'address1' => 'Rådhusplassen 1',
            'city' => 'Oslo',
            'zipCode' => '0037',
            'country' => 'NO',
        ],
        [
            'address1' => 'Neumanns gate 2a',
            'city' => 'Bergen',
            'zipCode' => '5011',
            'country' => 'NO',
        ],
        [
            'address1' => 'Place de l\'Hôtel de Ville',
            'city' => 'Paris',
            'zipCode' => '75004',
            'country' => 'FR',
        ],
        [
            'address1' => '5 Rue de l\'Hôtel de ville',
            'city' => 'Nice',
            'zipCode' => '06000',
            'country' => 'FR',
        ],
        [
            'address1' => 'Dame St',
            'city' => 'Dublin',
            'zipCode' => '8PVM+H5',
            'country' => 'IR',
        ],
    ];


    // Public Methods
    // =========================================================================

    public static function getTestAddress($country, $criteria = [])
    {
        $filter = array_merge(['country' => $country], $criteria);

        $addresses = ArrayHelper::whereMultiple(self::$_addresses, $filter);

        // Get the first address returned
        $address = ArrayHelper::firstValue($addresses) ?? [];

        // Rip out state/country for later. Will throw an error when trying to create the address.
        $countryIso = ArrayHelper::remove($address, 'country');
        $stateIso = ArrayHelper::remove($address, 'state');

        // Create a new address model, even if empty
        $address = new Address($address);

        // Add back the country/state
        if ($countryIso) {
            $country = Commerce::getInstance()->countries->getCountryByIso($countryIso);
            $address->countryId = $country->id ?? '';

            if ($country && $stateIso) {
                $state = Commerce::getInstance()->states->getStateByAbbreviation($country->id, $stateIso);
                $address->stateId = $state->id ?? '';
            }
        }

        return $address;
    }

    public static function getTestPackedBoxes($dimensionUnit, $weightUnit, $qty1 = 1, $qty2 = 1)
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
