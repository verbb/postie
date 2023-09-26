<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;

use verbb\shippy\carriers\PostNL as PostNLCarrier;

class PostNL extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'PostNL');
    }

    public static function getCarrierClass(): string
    {
        return PostNLCarrier::class;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('NO', ['locality' => 'Oslo']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('NO', ['locality' => 'Bergen']);
    }

}
