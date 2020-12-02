<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\base\StaticProvider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\inc\colissimo\ColissimoRates;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class Colissimo extends StaticProvider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'g';
    public $dimensionUnit = 'mm';


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Colissimo');
    }

    public function getServiceList(): array
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

    public function fetchShippingRates($order)
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
        // $country = Commerce::getInstance()->countries->getCountryByIso('FR');

        // $storeLocation = new craft\commerce\models\Address();
        // $storeLocation->address1 = 'Place de l\'Hôtel de Ville';
        // $storeLocation->city = 'Paris';
        // $storeLocation->zipCode = '75004';
        // $storeLocation->countryId = $country->id;

        // $country = Commerce::getInstance()->countries->getCountryByIso('FR');

        // $order->shippingAddress->address1 = '5 Rue de l\'Hôtel de ville';
        // $order->shippingAddress->city = 'Nice';
        // $order->shippingAddress->zipCode = '06000';
        // $order->shippingAddress->countryId = $country->id;
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
            // Rates contain boxes and prices - everything available for this region
            $rateAndBoxes = ColissimoRates::getRates($order->shippingAddress->country->iso, $handle);

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
                    'amount' => (float)$service['price'] ?? '',
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
