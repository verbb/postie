<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;

use verbb\shippy\carriers\Colissimo as ColissimoCarrier;

class Colissimo extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Colissimo');
    }

    public static function getCarrierClass(): string
    {
        return ColissimoCarrier::class;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('FR', ['locality' => 'Paris']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('FR', ['locality' => 'Nice']);
    }
}
