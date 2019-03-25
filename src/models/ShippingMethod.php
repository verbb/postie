<?php
namespace verbb\postie\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use craft\commerce\base\ShippingMethod as BaseShippingMethod;

class ShippingMethod extends BaseShippingMethod
{
    // Properties
    // =========================================================================

    public $provider;
    public $rate;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return $this->provider->getName();
    }

    public function getId()
    {
        return null;
    }

    public function getName(): string
    {
        return (string)$this->name;
    }

    public function getHandle(): string
    {
        return (string)$this->handle;
    }

    public function getShippingRules(): array
    {
        $shippingRule = new ShippingRule();
        $shippingRule->baseRate = $this->rate;
        $shippingRule->provider = $this->provider;

        return [$shippingRule];
    }

    public function getIsEnabled(): bool
    {
        return (bool)$this->enabled && (bool)$this->rate;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('postie/providers/' . $this->provider->getHandle() . '/shippingmethods/' . $this->getHandle());
    }
}
