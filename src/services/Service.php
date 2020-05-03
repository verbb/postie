<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;

use yii\base\Component;

class Service extends Component
{
    // Properties
    // =========================================================================

    private $_cachedShippingMethods;


    // Public Methods
    // =========================================================================

    public function registerShippingMethods(RegisterAvailableShippingMethodsEvent $event)
    {
        if (!$event->order) {
            return;
        }

        // Fetch all providers (enabled or otherwise)
        $providers = Postie::$plugin->getProviders()->getAllProviders();

        // Provide some class-based local cache, becaues this function is called multiple times
        // throughout an order-update lifecycle. Do this, even if caching is disabled
        if ($this->_cachedShippingMethods) {
            foreach ($this->_cachedShippingMethods as $shippingMethod) {
                $event->shippingMethods[] = $shippingMethod;
            }

            return;
        }

        foreach ($providers as $provider) {
            if (!$provider->enabled) {
                continue;
            }

            // Fetch all available shipping rates
            $rates = $provider->getShippingRates($event->order);

            // Only return shipping rates for methods we've enabled
            foreach ($provider->getShippingMethods() as $shippingMethod) {
                $rate = $rates[$shippingMethod->handle] ?? [];

                if ($rate) {
                    $shippingMethod->rate = $rate['amount'] ?? 0;
                    $shippingMethod->rateOptions = $rate['options'] ?? [];

                    $event->shippingMethods[] = $shippingMethod;

                    // Save it to a local class cache
                    $this->_cachedShippingMethods[] = $shippingMethod;
                }
            }
        }
    }
}
