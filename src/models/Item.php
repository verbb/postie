<?php
namespace verbb\postie\models;

use craft\base\Model;

class Item extends Model implements \DVDoug\BoxPacker\Item
{
    // Properties
    // =========================================================================

    private $description;
    private $width;
    private $length;
    private $depth;
    private $weight;
    private $keepFlat;


    // Public Methods
    // =========================================================================

    public function setDimensions($description, $width, $length, $depth, $weight)
    {
        $this->description = $description;
        $this->width = $width;
        $this->length = $length;
        $this->depth = $depth;
        $this->weight = $weight;
        $this->keepFlat = false;
    }

    public function getDescription(): string
    {
        return (string)$this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getWidth(): int
    {
        return (int)$this->width;
    }

    public function setWidth($value)
    {
        $this->width = $value;
    }

    public function getLength(): int
    {
        return (int)$this->length;
    }

    public function setLength($value)
    {
        $this->length = $value;
    }

    public function getDepth(): int
    {
        return (int)$this->depth;
    }

    public function setDepth($value)
    {
        $this->depth = $value;
    }

    public function getWeight(): int
    {
        return (int)$this->weight;
    }

    public function setWeight($value)
    {
        $this->weight = $value;
    }

    public function getKeepFlat(): bool
    {
        return (bool)$this->keepFlat;
    }

    public function setKeepFlat($value)
    {
        $this->keepFlat = $value;
    }
}
