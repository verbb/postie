<?php
namespace verbb\postie\base;

use verbb\postie\base\Provider;
use verbb\postie\events\PackOrderEvent;
use verbb\postie\models\Box;
use verbb\postie\models\PackedBoxes;

use Craft;
use craft\commerce\elements\Order;

use DVDoug\BoxPacker\InfalliblePacker;

class StaticProvider extends Provider
{
    // Public Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public function fetchShippingRates($order)
    {
        return [];
    }

    public function getPackagesAndRates($rateAndBoxes, $serviceHandle, $order)
    {
        // We need to return the best-fitting box (including price) for each rate.
        $packer = new InfalliblePacker();

        // Create new boxes for each available box, storing the price for later
        foreach ($rateAndBoxes as $name => $boxInfo) {
            $packer->addBox(new Box([
                'reference' => $name,
                'outerWidth' => $boxInfo['width'],
                'outerLength' => $boxInfo['length'],
                'outerDepth' => $boxInfo['height'],
                'emptyWeight' => 0,
                'innerWidth' => $boxInfo['width'],
                'innerLength' => $boxInfo['length'],
                'innerDepth' => $boxInfo['height'],
                'maxWeight' => $boxInfo['weight'],
                'price' => $boxInfo['price'],
            ]));
        }

        // Prepare the boxes, which is a little stricter than usual if it can't be packed, then no rates
        $packedBoxes = $this->packOrderIntoBoxes($order, $packer)->getSerializedPackedBoxList();

        if ($packedBoxes) {
            $totalPrice = 0;

            // Because we might be dealing with multiple boxes, sum the price
            foreach ($packedBoxes as $key => $packedBox) {
                $totalPrice += $packedBox['price'] ?? 0;
            }

            return [
                'service' => $serviceHandle,
                'price' => $totalPrice,
                'packages' => $packedBoxes,
            ];
        }

        return [];
    }


    // Protected Methods
    // =========================================================================

    protected function packOrderIntoBoxes(Order $order, $packer)
    {
        $packOrderEvent = new PackOrderEvent([
            'packer' => $packer,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(Provider::EVENT_BEFORE_PACK_ORDER)) {
            $this->trigger(Provider::EVENT_BEFORE_PACK_ORDER, $packOrderEvent);
        }

        if ($this->packingMethod === Provider::PACKING_SINGLE_BOX || $this->packingMethod === Provider::PACKING_BOX) {
            foreach ($order->getLineItems() as $lineItem) {
                if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                    $packer->addItem($boxItem, $lineItem->qty);
                }
            }
        }

        // If packing boxes individually, create boxes exactly the same size as each item
        if ($this->packingMethod === Provider::PACKING_PER_ITEM) {
            foreach ($order->getLineItems() as $lineItem) {
                // Don't forget to factor in quantities
                for ($i = 0; $i < $lineItem->qty; $i++) { 
                    // Add the single item to the single box
                    if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                        $packer->addItem($boxItem, 1);
                    }
                }
            }
        }

        // Get a collection of packed boxes - ignoring any unpacked ones
        $packedBoxes = $packer->pack();

        if (!$packedBoxes) {
            Provider::error(Craft::t('postie', 'Unable to pack order for “{pack}”.', ['pack' => $this->packingMethod]));
        }

        $packedBoxes = new PackedBoxes($packedBoxes, $this->weightUnit, $this->dimensionUnit);

        $packOrderEvent = new PackOrderEvent([
            'packer' => $packer,
            'order' => $order,
            'packedBoxes' => $packedBoxes,
        ]);

        if ($this->hasEventHandlers(Provider::EVENT_AFTER_PACK_ORDER)) {
            $this->trigger(Provider::EVENT_AFTER_PACK_ORDER, $packOrderEvent);
        }

        return $packOrderEvent->packedBoxes;
    }

}
