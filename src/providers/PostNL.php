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

    public static function getServiceList(): array
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
    

    // Properties
    // =========================================================================

    public ?string $handle = 'postNl';
    public string $dimensionUnit = 'mm';
    public string $weightUnit = 'g';


    // Public Methods
    // =========================================================================

    public function fetchShippingRates($order): ?array
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        //
        // TESTING
        //
        // Domestic
        // $storeLocation = TestingHelper::getTestAddress('NL', ['locality' => 'Rotterdam']);
        // $order->shippingAddress = TestingHelper::getTestAddress('NL', ['locality' => 'Amsterdam'], $order);

        // International
        // $order->shippingAddress = TestingHelper::getTestAddress('US', ['administrativeArea' => 'CA'], $order);
        //
        // 
        //

        // Get all enabled services
        if ($this->restrictServices) {
            $services = ArrayHelper::where($this->services, 'enabled', true);
        } else {
            $services = self::getServiceList();
        }

        $allRates = [];

        foreach ($services as $handle => $label) {
            $countryCode = $order->shippingAddress->countryCode ?? '';

            // Rates contain boxes and prices - everything available for this region
            $rateAndBoxes = PostNLRates::getRates($countryCode, $handle);

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
            Provider::info($this, Craft::t('postie', 'No services found.'));
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
