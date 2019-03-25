<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;

use yii\base\Component;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function registerShippingMethods(RegisterAvailableShippingMethodsEvent $event)
    {
        if (!$event->order) {
            return;
        }

        // Fetch all providers (enabled or otherwise)
        $providers = Postie::$plugin->getProviders()->getAllProviders();

        foreach ($providers as $provider) {
            if (!$provider->enabled) {
                continue;
            }

            // Fetch all available shipping rates
            $rates = $provider->getShippingRates($event->order);

            // Only return shipping rates for methods we've enabled
            foreach ($provider->getShippingMethods() as $shippingMethod) {
                $rate = $rates[$shippingMethod->handle] ?? 0;

                if ($rate) {
                    $shippingMethod->rate = $rate;
                    $event->shippingMethods[] = $shippingMethod;
                }
            }
        }
    }
}
