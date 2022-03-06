<?php
namespace verbb\postie\models;

use verbb\postie\base\Provider;
use verbb\postie\events\ModifyShippingRuleEvent;

use craft\helpers\UrlHelper;

use craft\commerce\base\ShippingMethod as BaseShippingMethod;

class ShippingMethod extends BaseShippingMethod
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_SHIPPING_RULE = 'modifyShippingRule';


    // Properties
    // =========================================================================

    public ?Provider $provider = null;
    public ?float $rate = null;
    public ?array $rateOptions = null;
    public mixed $shippingMethodCategories = null;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return $this->provider->name;
    }

    public function getId(): ?int
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
        $shippingRule->description = $this->name;
        $shippingRule->baseRate = $this->rate;
        $shippingRule->provider = $this->provider;
        $shippingRule->shippingMethod = $this;
        $shippingRule->options = $this->rateOptions;

        // Drop any settings for the provider, these are returned with calculation requests
        if (property_exists($shippingRule->provider, 'settings')) {
            $shippingRule->provider->settings = [];
        }

        // Allow plugins to modify the rule
        $modifyRuleEvent = new ModifyShippingRuleEvent([
            'provider' => $this->provider,
            'shippingMethod' => $this,
            'shippingRule' => $shippingRule,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_SHIPPING_RULE)) {
            $this->trigger(self::EVENT_MODIFY_SHIPPING_RULE, $modifyRuleEvent);
        }

        return [$modifyRuleEvent->shippingRule];
    }

    public function getIsEnabled(): bool
    {
        return $this->enabled && isset($this->rate);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('postie/settings/shipping-methods/' . $this->provider->handle . '/' . $this->getHandle());
    }
}
