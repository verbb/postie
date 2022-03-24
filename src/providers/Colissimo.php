<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\base\StaticProvider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\inc\colissimo\ColissimoRates;

use Craft;
use craft\helpers\ArrayHelper;

use craft\commerce\Plugin as Commerce;

class Colissimo extends StaticProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Colissimo');
    }

    public static function getServiceList(): array
    {
        return [
            'france' => 'France',
            'emballage-france' => 'Emballage France',
            'outremer' => 'Outre-Mer',
            'europe' => 'Europe',
            'economique-outremer' => 'Economique Outre-Mer',
            'international' => 'International',
            'emballage-international' => 'Emballage International',
        ];
    }


    // Properties
    // =========================================================================

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
        // $country = Commerce::getInstance()->countries->getCountryByIso('FR');

        // $storeLocation = new craft\elements\Address();
        // $storeLocation->addressLine1 = 'Place de l\'Hôtel de Ville';
        // $storeLocation->locality = 'Paris';
        // $storeLocation->postalCode = '75004';
        // $storeLocation->countryId = $country->id;

        // $country = Commerce::getInstance()->countries->getCountryByIso('FR');

        // $order->shippingAddress->addressLine1 = '5 Rue de l\'Hôtel de ville';
        // $order->shippingAddress->locality = 'Nice';
        // $order->shippingAddress->postalCode = '06000';
        // $order->shippingAddress->countryId = $country->id;
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
            $rateAndBoxes = ColissimoRates::getRates($countryCode, $handle);

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
