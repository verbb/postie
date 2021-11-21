<?php
namespace verbb\postie\providers;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\base\StaticProvider;
use verbb\postie\events\ModifyRatesEvent;
use verbb\postie\helpers\TestingHelper;
use verbb\postie\inc\royalmail\RoyalMailRates;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class RoyalMail extends StaticProvider
{
    // Properties
    // =========================================================================

    public $weightUnit = 'g';
    public $dimensionUnit = 'mm';


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Royal Mail');
    }

    public function getServiceList(): array
    {
        return [
            // Domestic
            'first-class' => 'Royal Mail 1st Class',
            'first-class-signed' => 'Royal Mail Signed For® 1st Class',
            'second-class' => 'Royal Mail 2nd Class',
            'second-class-signed' => 'Royal Mail Signed For® 2nd Class',

            'special-delivery-9am' => 'Royal Mail Special Delivery Guaranteed by 9am®',
            'special-delivery-1pm' => 'Royal Mail Special Delivery Guaranteed by 1pm®',

            'parcelforce-express-9' => 'Parcelforce Worldwide Express 9',
            'parcelforce-express-10' => 'Parcelforce Worldwide Express 10',
            'parcelforce-express-am' => 'Parcelforce Worldwide Express AM',
            'parcelforce-express-24' => 'Parcelforce Worldwide Express 24',
            'parcelforce-express-48' => 'Parcelforce Worldwide Express 48',

            // International
            'international-standard' => 'Royal Mail International Standard',
            'international-tracked-signed' => 'Royal Mail International Tracked & Signed',
            'international-tracked' => 'Royal Mail International Tracked',
            'international-signed' => 'Royal Mail International Signed',
            'international-economy' => 'Royal Mail International Economy',

            'parcelforce-irelandexpress' => 'Parcelforce Worldwide Ireland Express',
            'parcelforce-globaleconomy' => 'Parcelforce Worldwide Global Economy',
            'parcelforce-globalexpress' => 'Parcelforce Worldwide Global Express',
            'parcelforce-globalpriority' => 'Parcelforce Worldwide Global Priority',
            'parcelforce-globalvalue' => 'Parcelforce Worldwide Global Value',
        ];
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/postie/resources/dist/img/royal-mail.png", true);
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
        // $storeLocation = TestingHelper::getTestAddress('GB', ['city' => 'London']);
        // $order->shippingAddress = TestingHelper::getTestAddress('GB', ['city' => 'Glasgow']);

        // // International
        // $order->shippingAddress = TestingHelper::getTestAddress('AU', ['state' => 'VIC']);
        // $order->shippingAddress = TestingHelper::getTestAddress('IR');
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
            $rateAndBoxes = RoyalMailRates::getRates($order->shippingAddress->country->iso, $handle);

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
