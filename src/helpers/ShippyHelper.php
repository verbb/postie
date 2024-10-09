<?php
namespace verbb\postie\helpers;

use craft\elements\Address;

use craft\commerce\elements\Order;

use verbb\shippy\models\Address as ShippyAddress;

class ShippyHelper
{
    // Static Methods
    // =========================================================================

    public static function toAddress(Order $order, Address $address): ShippyAddress
    {
        // For things like the store address, there's no first/last name, but we need to supply a name regardless.
        $firstName = (string)$address->firstName ?: $address->title;

        return new ShippyAddress([
            'email' => (string)$order->email,
            'firstName' => $firstName,
            'lastName' => (string)$address->lastName,
            'companyName' => (string)$address->organization,
            'street1' => (string)$address->addressLine1,
            'street2' => (string)$address->addressLine2,
            'city' => (string)$address->locality,
            'stateProvince' => (string)$address->administrativeArea,
            'postalCode' => (string)$address->postalCode,
            'countryCode' => $address->countryCode,
        ]);
    }
}
