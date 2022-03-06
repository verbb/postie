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

    private mixed $packedBoxList = null;
    private ?string $weightUnit = null;
    private ?string $dimensionUnit = null;


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

    public function getSerializedPackedBoxList(): array
    {
        $list = [];

        foreach ($this->packedBoxList as $packedBox) {
            // Convert the box values stored in g/mm to what we need them as
            $weight = (new Mass($packedBox->getWeight(), 'g'))->toUnit($this->weightUnit);
            $width = (new Length($packedBox->getBox()->getOuterWidth(), 'mm'))->toUnit($this->dimensionUnit);
            $length = (new Length($packedBox->getBox()->getOuterLength(), 'mm'))->toUnit($this->dimensionUnit);
            $height = (new Length($packedBox->getBox()->getOuterDepth(), 'mm'))->toUnit($this->dimensionUnit);

            // Pretty much all providers have restrictions on large numbers, round to 2 decimals
            $weight = round($weight, 2);
            $height = round($height, 2);
            $width = round($width, 2);
            $length = round($length, 2);

            // Just in case there's a 0 weight item, we want to set a min weight. This can happen due to how the 
            // box-packer only handles integers. https://github.com/dvdoug/BoxPacker/discussions/241
            if ($weight == 0) {
                $weight = 0.01;
            }

            $list[] = [
                'name' => $packedBox->getBox()->getReference(),
                'weight' => $weight,
                'width' => $width,
                'length' => $length,
                'height' => $height,
                'type' => $packedBox->getBox()->getType() ?? '',
                'price' => $packedBox->getBox()->getPrice() ?? 0,
            ];
        }

        return $list;
    }

    public function getTotalWeight(): float
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

    public function getTotalLength(): float
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

    public function getTotalHeight(): float
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

    public function getTotalWidth(): float
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

    public function getTotalPrice(): float|int
    {
        $totalPrice = 0;

        foreach ($this->packedBoxList as $packedBox) {
            foreach ($packedBox->getItems() as $packedItem) {
                $totalPrice += (float)$packedItem->getItem()->getItemValue();
            }
        }

        return $totalPrice;
    }
}
