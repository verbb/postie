<?php
namespace verbb\postie\helpers;

use craft\helpers\ArrayHelper;
use craft\commerce\Plugin as Commerce;

class PostieHelper
{
    // Public Methods
    // =========================================================================

    public static function getValueByKey(array $array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    public static function getSignature($order, $prefix = '')
    {
        $totalLength = 0;
        $totalWidth = 0;
        $totalHeight = 0;

        foreach (self::getOrderLineItems($order) as $key => $lineItem) {
            $totalLength += ($lineItem->qty * $lineItem->length);
            $totalWidth += ($lineItem->qty * $lineItem->width);
            $totalHeight += ($lineItem->qty * $lineItem->height);
        }

        $addressLines = $order->shippingAddress->addressLines ?? [];

        $signature = implode('.', [
            $prefix,
            $order->getTotalQty(),
            $order->getTotalWeight(),
            $totalWidth,
            $totalHeight,
            $totalLength,
            implode('.', $addressLines),
        ]);

        return md5($signature);
    }

    public static function getOrderLineItems($order)
    {
        $items = [];
        $discounts = Commerce::getInstance()->getDiscounts()->getAllActiveDiscounts($order);
        $hasLineItemLevelShippingRelatedDiscounts = (bool)ArrayHelper::firstWhere($discounts, 'hasFreeShippingForMatchingItems', true, false);

        foreach ($order->getLineItems() as $item) {
            $hasFreeShippingFromDiscount = false;

            if ($hasLineItemLevelShippingRelatedDiscounts) {
                foreach ($discounts as $discount) {
                    $matchedLineItem = Commerce::getInstance()->getDiscounts()->matchLineItem($item, $discount, true);
                    
                    if ($discount->hasFreeShippingForMatchingItems && $matchedLineItem) {
                        $hasFreeShippingFromDiscount = true;
                        break;
                    }

                    if ($matchedLineItem && $discount->stopProcessing) {
                        break;
                    }
                }
            }

            $freeShippingFlagOnProduct = $item->purchasable->hasFreeShipping();
            $shippable = Commerce::getInstance()->getPurchasables()->isPurchasableShippable($item->getPurchasable());
            
            if (!$freeShippingFlagOnProduct && !$hasFreeShippingFromDiscount && $shippable) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
