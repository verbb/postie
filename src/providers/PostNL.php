<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\base\StaticProvider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\inc\postnl\PostNLRates;

use Craft;
use craft\helpers\ArrayHelper;

use craft\commerce\Plugin as Commerce;

class PostNL extends StaticProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'PostNL');
    }

    // Properties
    // =========================================================================

    public ?string $handle = 'postNl';
    public string $dimensionUnit = 'mm';
    public string $weightUnit = 'g';


    // Public Methods
    // =========================================================================

    public function getServiceList(): array
    {
        return [
            'brief' => 'Brief',
            'brievenbuspakje' => 'Brievenbuspakje',
            'pakket-no-track-and-trace' => 'Pakket no Track & Trace',
            'pakket' => 'Pakket',
            'aangetekend' => 'Aangetekend',
            'verzekerservice' => 'Verzekerservice',
            'betaalservice' => 'Betaalservice',
        ];
    }

    public function fetchShippingRates($order): ?array
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        //
        // TESTING
        //
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('NL', ['city' => 'Rotterdam']);
        // $order->shippingAddress = TestingHelper::getTestAddress('NL', ['city' => 'Amsterdam']);

        // International
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['state' => 'CA']);
        //
        // 
        //

        // Get all enabled services
        if ($this->restrictServices) {
            $services = ArrayHelper::where($this->services, 'enabled', true);
        } else {
            $services = $this->getServiceList();
        }

        $allRates = [];

        foreach ($services as $handle => $label) {
            $countryIso = $order->shippingAddress->country->iso ?? '';

            // Rates contain boxes and prices - everything available for this region
            $rateAndBoxes = PostNLRates::getRates($countryIso, $handle);

            // Determine the best packages, and calculate the total price
            $rate = $this->getPackagesAndRates($rateAndBoxes, $handle, $order);

            if ($rate) {
                $allRates[] = $rate;
            }
        }

        // Hopefully we have our rates now, bundle them up!
        if ($allRates) {
            foreach ($allRates as $service) {
                $this->_rates[$service['service']] = [
                    'amount' => (float)($service['price'] ?? 0),
                    'options' => $service,
                ];
            }
        } else {
            Provider::log($this, Craft::t('postie', 'No services found.'));
        }

        // Allow rate modification via events
        $modifyRatesEvent = new ModifyRatesEvent([
            'rates' => $this->_rates,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
            $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
        }

        $this->_rates = $modifyRatesEvent->rates;

        return $this->_rates;
    }

}
