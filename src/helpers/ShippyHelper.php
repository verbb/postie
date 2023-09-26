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
        return new ShippyAddress([
            'email' => $order->email,
            'firstName' => (string)$address->firstName,
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
