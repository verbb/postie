<?php
namespace verbb\postie\helpers;

use Craft;

class PostieHelper
{
    // Static Methods
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

    public static function getSignature($order, $prefix = ''): string
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
            implode('.', self::getAddressLines($order->shippingAddress)),
        ]);

        return md5($signature);
    }

    public static function getAddressLines($address = null): array
    {
        if (!$address) {
            return [];
        }

        $addressLines = [
            'countryCode' => $address->countryCode,
            'administrativeArea' => $address->administrativeArea,
            'locality' => $address->locality,
            'dependentLocality' => $address->dependentLocality,
            'postalCode' => $address->postalCode,
            'sortingCode' => $address->sortingCode,
            'addressLine1' => $address->addressLine1,
            'addressLine2' => $address->addressLine2,
            'organization' => $address->organization,
            'organizationTaxId' => $address->organizationTaxId,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'title' => $address->title,
            'fullName' => $address->fullName,
            'status' => $address->status,
        ];

        // Remove blank lines
        $addressLines = array_filter($addressLines);

        array_walk($addressLines, function(&$value) {
            $value = Craft::$app->getFormatter()->asText($value);
        });

        return $addressLines;
    }
}
