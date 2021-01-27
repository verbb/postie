<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\Model;

use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

class PackedBoxes extends Model
{
    // Properties
    // =========================================================================

    private $packedBoxList;
    private $weightUnit;
    private $dimensionUnit;


    // Public Methods
    // =========================================================================

    public function __construct($packedBoxList, $weightUnit, $dimensionUnit) {
        $this->packedBoxList = $packedBoxList;
        $this->weightUnit = $weightUnit;
        $this->dimensionUnit = $dimensionUnit;
    }

    public function getPackedBoxList()
    {
        return $this->packedBoxList;
    }

    public function getSerializedPackedBoxList()
    {
        $list = [];

        foreach ($this->packedBoxList as $packedBox) {
            // Convert the box values stored in g/mm to what we need them as
            $weight = (new Mass($packedBox->getWeight(), 'g'))->toUnit($this->weightUnit);
            $width = (new Length($packedBox->getBox()->getOuterWidth(), 'mm'))->toUnit($this->dimensionUnit);
            $length = (new Length($packedBox->getBox()->getOuterLength(), 'mm'))->toUnit($this->dimensionUnit);
            $height = (new Length($packedBox->getBox()->getOuterDepth(), 'mm'))->toUnit($this->dimensionUnit);

            // Pretty much all providers have restrictions on large numbers,
            $weight = round($weight, 2);
            $height = round($height, 2);
            $width = round($width, 2);
            $length = round($length, 2);

            $listItem = [
                'name' => $packedBox->getBox()->getReference(),
                'weight' => $weight,
                'width' => $width,
                'length' => $length,
                'height' => $height,
            ];

            if ($type = $packedBox->getBox()->getType()) {
                $listItem['type'] = $type;
            }

            if ($price = $packedBox->getBox()->getPrice()) {
                $listItem['price'] = $price;
            }

            $list[] = $listItem;
        }

        return $list;
    }

    public function getTotalWeight()
    {
        $totalBoxWeight = 0;

        foreach ($this->packedBoxList as $packedBox) {
            $totalBoxWeight += $packedBox->getWeight();
        }

        // Box weights are always in grams
        $totalBoxWeight = (new Mass($totalBoxWeight, 'g'))->toUnit($this->weightUnit);
        $totalBoxWeight = round($totalBoxWeight, 2);

        return $totalBoxWeight;
    }

    public function getTotalLength()
    {
        $totalBoxLength = 0;

        foreach ($this->packedBoxList as $packedBox) {
            $totalBoxLength += $packedBox->getBox()->getOuterLength();
        }

        // Box lengths are always in mm
        $totalBoxLength = (new Length($totalBoxLength, 'mm'))->toUnit($this->dimensionUnit);
        $totalBoxLength = round($totalBoxLength, 2);

        return $totalBoxLength;
    }

    public function getTotalHeight()
    {
        $totalBoxHeight = 0;

        foreach ($this->packedBoxList as $packedBox) {
            $totalBoxHeight += $packedBox->getBox()->getOuterDepth();
        }

        // Box heights are always in mm
        $totalBoxHeight = (new Length($totalBoxHeight, 'mm'))->toUnit($this->dimensionUnit);
        $totalBoxHeight = round($totalBoxHeight, 2);

        return $totalBoxHeight;
    }

    public function getTotalWidth()
    {
        $totalBoxWidth = 0;

        foreach ($this->packedBoxList as $packedBox) {
            $totalBoxWidth += $packedBox->getBox()->getOuterWidth();
        }

        // Box widths are always in mm
        $totalBoxWidth = (new Length($totalBoxWidth, 'mm'))->toUnit($this->dimensionUnit);
        $totalBoxWidth = round($totalBoxWidth, 2);

        return $totalBoxWidth;
    }
}
