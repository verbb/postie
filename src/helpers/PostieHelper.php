<?php
namespace verbb\postie\helpers;

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

        foreach ($order->lineItems as $key => $lineItem) {
            $totalLength += ($lineItem->qty * $lineItem->length);
            $totalWidth += ($lineItem->qty * $lineItem->width);
            $totalHeight += ($lineItem->qty * $lineItem->height);
        }

        $signature = implode('.', [
            $prefix,
            $order->getTotalQty(),
            $order->getTotalWeight(),
            $totalWidth,
            $totalHeight,
            $totalLength,
            implode('.', $order->shippingAddress->addressLines),
        ]);

        return md5($signature);
    }
}
