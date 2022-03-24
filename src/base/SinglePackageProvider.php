<?php
namespace verbb\postie\base;

use verbb\postie\events\ModifyRatesEvent;

use craft\commerce\Plugin as Commerce;

class SinglePackageProvider extends Provider
{
    // Properties
    // =========================================================================

    private array $_cachedPackageRates = [];


    // Public Methods
    // =========================================================================

    public function fetchShippingRates($order): ?array
    {
        // If we've locally cached the results, return that
        if ($this->_rates) {
            return $this->_rates;
        }

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        // Pack the content of the order into boxes
        $packedBoxes = $this->packOrder($order)->getSerializedPackedBoxList();

        // Allow location and packages modification via events
        $this->beforeFetchRates($storeLocation, $packedBoxes, $order);

        // Because the API doesn't support multi-boxing, and we might have multiple boxes, we need to
        // make potentially several API requests to fetch the correct total rate.
        foreach ($packedBoxes as $packedBox) {
            // For multi-packed requests where we're fetching the exact same package dimensions/weights, we can cache the request.
            // For instance, 5 packages exactly the same don't need 5 API requests, they'll all be the same.
            if ($cachedRate = $this->_getCachedRateForBox($packedBox)) {
                $this->setRate($packedBox, $cachedRate);

                continue;
            }

            $response = $this->fetchShippingRate($order, $storeLocation, $packedBox, $packedBoxes);
        }

        // Allow rate modification via events
        $modifyRatesEvent = new ModifyRatesEvent([
            'rates' => $this->_rates,
            'order' => $order,
            'response' => $response ?? null,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_RATES)) {
            $this->trigger(self::EVENT_MODIFY_RATES, $modifyRatesEvent);
        }

        $this->_rates = $modifyRatesEvent->rates;

        return $this->_rates;
    }


    // Protected Methods
    // =========================================================================

    protected function setRate($packedBox, $payload): void
    {
        $key = $payload['key'];
        $rate = $payload['value'];

        // Store the rate, just in case we request it again for multi-packages
        $this->_setCachedRateForBox($packedBox, $payload);

        // Check if there are existing rates. We might be combining prices for multiple packages
        $existingAmount = $this->_rates[$key]['amount'] ?? null;

        if ($existingAmount) {
            $this->_rates[$key]['amount'] += $rate['amount'];
        } else {
            $this->_rates[$key] = $rate;
        }
    }


    // Private Methods
    // =========================================================================

    private function _setCachedRateForBox($packedBox, $payload): void
    {
        $cacheKey = implode('.', $packedBox);

        $this->_cachedPackageRates[$cacheKey] = $payload;
    }

    private function _getCachedRateForBox($packedBox)
    {
        $cacheKey = implode('.', $packedBox);

        return $this->_cachedPackageRates[$cacheKey] ?? null;
    }

}
