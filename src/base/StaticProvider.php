<?php
namespace verbb\postie\base;

use verbb\postie\events\PackOrderEvent;
use verbb\postie\helpers\PostieHelper;
use verbb\postie\models\Box;
use verbb\postie\models\PackedBoxes;

use Craft;
use craft\commerce\elements\Order;

use DVDoug\BoxPacker\BoxList;
use DVDoug\BoxPacker\InfalliblePacker;

class StaticProvider extends Provider
{
    // Public Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public function fetchShippingRates($order): ?array
    {
        return [];
    }

    public function getPackagesAndRates($rateAndBoxes, $serviceHandle, $order): array
    {
        $boxes = [];

        // We need to return the best-fitting box (including price) for each rate.
        $packer = new InfalliblePacker();

        // Ensure we sort boxes now by price. Could maybe be done in a custom BoxList?
        uasort($rateAndBoxes, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        // Create new boxes for each available box, storing the price for later
        foreach ($rateAndBoxes as $name => $boxInfo) {
            $boxes[] = new Box([
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
                'maxItemValue' => $boxInfo['itemValue'] ?? null,
            ]);
        }

        // We need to use a custom BoxList to maintain our custom order of price-cheapest
        // BoxPacker will by default just pick the smallest box first.
        $boxList = new BoxList();

        // Add boxes to the BoxList, retaining their order
        $boxList = $boxList::fromArray($boxes, true);

        // Add the BoxList to the packer, instead of each box (which would be easier)
        $packer->setBoxes($boxList);

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
            foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
                if ($boxItem = $this->getBoxItemFromLineItem($lineItem)) {
                    $packer->addItem($boxItem, $lineItem->qty);
                }
            }
        }

        // If packing boxes individually, create boxes exactly the same size as each item
        if ($this->packingMethod === Provider::PACKING_PER_ITEM) {
            foreach (PostieHelper::getOrderLineItems($order) as $lineItem) {
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
            Provider::error($this, Craft::t('postie', 'Unable to pack order for “{pack}”.', ['pack' => $this->packingMethod]));
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
