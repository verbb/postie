<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\Model;

class Box extends Model implements \DVDoug\BoxPacker\Box
{
    // Properties
    // =========================================================================

    private $reference;
    private $outerWidth;
    private $outerLength;
    private $outerDepth;
    private $emptyWeight;
    private $innerWidth;
    private $innerLength;
    private $innerDepth;
    private $maxWeight;
    private $type = '';


    // Public Methods
    // =========================================================================

    public function setDimensions($reference, $width, $length, $depth, $weight)
    {
        $this->reference = $reference;
        $this->outerWidth = $width;
        $this->outerLength = $length;
        $this->outerDepth = $depth;
        $this->emptyWeight = 0;
        $this->innerWidth = $width;
        $this->innerLength = $length;
        $this->innerDepth = $depth;
        $this->maxWeight = $weight;
    }

    public function getReference(): string
    {
        return (string)$this->reference;
    }

    public function setReference($value)
    {
        $this->reference = $value;
    }

    public function getOuterWidth(): int
    {
        return (int)$this->outerWidth;
    }

    public function setOuterWidth($value)
    {
        $this->outerWidth = $value;
    }

    public function getOuterLength(): int
    {
        return (int)$this->outerLength;
    }

    public function setOuterLength($value)
    {
        $this->outerLength = $value;
    }

    public function getOuterDepth(): int
    {
        return (int)$this->outerDepth;
    }

    public function setOuterDepth($value)
    {
        $this->outerDepth = $value;
    }

    public function getEmptyWeight(): int
    {
        return (int)$this->emptyWeight;
    }

    public function setEmptyWeight($value)
    {
        $this->emptyWeight = $value;
    }

    public function getInnerWidth(): int
    {
        return (int)$this->innerWidth;
    }

    public function setInnerWidth($value)
    {
        $this->innerWidth = $value;
    }

    public function getInnerLength(): int
    {
        return (int)$this->innerLength;
    }

    public function setInnerLength($value)
    {
        $this->innerLength = $value;
    }

    public function getInnerDepth(): int
    {
        return (int)$this->innerDepth;
    }

    public function setInnerDepth($value)
    {
        $this->innerDepth = $value;
    }

    public function getMaxWeight(): int
    {
        return (int)$this->maxWeight;
    }

    public function setMaxWeight($value)
    {
        $this->maxWeight = $value;
    }

    public function getType(): string
    {
        return (string)$this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }
}
