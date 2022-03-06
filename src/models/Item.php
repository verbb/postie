<?php
namespace verbb\postie\models;

use craft\base\Model;

class Item extends Model implements \DVDoug\BoxPacker\Item
{
    // Properties
    // =========================================================================

    private ?string $description = null;
    private ?int $width = null;
    private ?int $length = null;
    private ?int $depth = null;
    private ?int $weight = null;
    private ?bool $keepFlat = null;
    private ?float $itemValue = null;


    // Public Methods
    // =========================================================================

    public function setDimensions($description, $width, $length, $depth, $weight): void
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

    public function setDescription($value): void
    {
        $this->description = $value;
    }

    public function getWidth(): int
    {
        return (int)$this->width;
    }

    public function setWidth($value): void
    {
        $this->width = $value;
    }

    public function getLength(): int
    {
        return (int)$this->length;
    }

    public function setLength($value): void
    {
        $this->length = $value;
    }

    public function getDepth(): int
    {
        return (int)$this->depth;
    }

    public function setDepth($value): void
    {
        $this->depth = $value;
    }

    public function getWeight(): int
    {
        return (int)$this->weight;
    }

    public function setWeight($value): void
    {
        $this->weight = $value;
    }

    public function getKeepFlat(): bool
    {
        return (bool)$this->keepFlat;
    }

    public function setKeepFlat($value): void
    {
        $this->keepFlat = $value;
    }

    public function getItemValue()
    {
        return $this->itemValue;
    }

    public function setItemValue($value): void
    {
        $this->itemValue = $value;
    }
}
