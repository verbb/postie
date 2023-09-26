<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;

use Craft;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingProvider extends Provider implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Public Methods
    // =========================================================================

    public static function getCarrierClass(): string
    {
        return '';
    }

    public static function typeName(): string
    {
        return Craft::t('postie', 'Missing Provider');
    }

    public function getDescription(): string
    {
        return '';
    }
}
