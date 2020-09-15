<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\events\RegisterAvailableShippingMethodsEvent;

use yii\base\Component;
use yii\base\Event;

class Service extends Component
{
    // Properties
    // =========================================================================

    private $_cachedShippingMethods;


    // Public Methods
    // =========================================================================

    public function onAfterSaveOrder(Event $event)
    {
        if (!is_a($event->element, Order::class)) {
            return;
        }

        $settings = Postie::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        // Only care about this being enabled
        if (!$settings->manualFetchRates) {
            return;
        }

        if ($request->getIsConsoleRequest()) {
            return;
        }

        // Check it matches the config variable
        if ($request->getParam('fetchRatesPostValue') == $settings->fetchRatesPostValue) {
            Craft::$app->getSession()->set('postieManualFetchRates', true);
        }
    }

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

            // If this is a completed order, fetch the shipping methods, but DO NOT fetch live rates.
            // This is so we can still have registered shipping methods for `order.shippingMethod.name`
            if ($event->order->isCompleted) {
                foreach ($provider->getShippingMethods() as $shippingMethod) {
                    $shippingMethod->rate = 0;
                    $shippingMethod->rateOptions = [];

                    $event->shippingMethods[] = $shippingMethod;
                }

                continue;
            }

            // Fetch all available shipping rates
            $rates = $provider->getShippingRates($event->order);

            // Only return shipping rates for methods we've enabled
            foreach ($provider->getShippingMethods($event->order) as $shippingMethod) {
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
