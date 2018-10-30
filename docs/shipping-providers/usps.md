# USPS

In order to use USPS, you'll need to ensure you are using a valid United States address as your origin. You'll also need to ensure your Craft Commerce default currency is set to USD.

### How to get API access

Register for an API username via the [Registration for USPS Web Tools](https://registration.shippingapis.com/) form. Add this username in the provider information either through the control panel, or in the configuration file.

### Services

The below service are available with USPS for domestic and international customer destination addresses.

- Domestic
    
    - `PRIORITY_MAIL_EXPRESS_1_DAY`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_SUNDAY_HOLIDAY_DELIVERY`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY`
    - `PRIORITY_MAIL_EXPRESS_2_DAY`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP`
    - `PRIORITY_MAIL_1_DAY`
    - `PRIORITY_MAIL_1_DAY_LARGE_FLAT_RATE_BOX`
    - `PRIORITY_MAIL_1_DAY_MEDIUM_FLAT_RATE_BOX`
    - `PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_BOX`
    - `PRIORITY_MAIL_1_DAY_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_1_DAY_LEGAL_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_1_DAY_PADDED_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_1_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_ENVELOPE`
    - `PRIORITY_MAIL_1_DAY_WINDOW_FLAT_RATE_ENVELOPE`
    - `FIRST_CLASS_MAIL`
    - `FIRST_CLASS_MAIL_STAMPED_LETTER`
    - `FIRST_CLASS_MAIL_METERED_LETTER`
    - `FIRST_CLASS_MAIL_LARGE_ENVELOPE`
    - `FIRST_CLASS_MAIL_POSTCARDS`
    - `FIRST_CLASS_MAIL_LARGE_POSTCARDS`
    - `FIRST_CLASS_PACKAGE_SERVICE_RETAIL`
    - `MEDIA_MAIL_PARCEL`
    - `LIBRARY_MAIL_PARCEL`
- International
    
    - `USPS_GXG_ENVELOPES`
    - `PRIORITY_MAIL_EXPRESS_INTERNATIONAL`
    - `PRIORITY_MAIL_INTERNATIONAL`
    - `PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX`
    - `PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX`
    - `FIRST_CLASS_MAIL_INTERNATIONAL`
    - `FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE`

### Control Panel

![USPS Provider](/uploads/plugins/postie/usps-provider.png)

### Configuration File

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'USPS' => [
        'name' => 'USPS',
        'markUpRate' => '<yourMarkUpRate>',
        'markUpBase' => '<value>',

        'settings' => [
            'username' => '<yourUsername>',
        ],

        'services' => [
            'PRIORITY_MAIL_EXPRESS_1_DAY' => 'Priority Mail Express',
            'PRIORITY_MAIL_1_DAY' => 'Priority Mail',
            'FIRST_CLASS_MAIL' => 'First-Class Mail',
            'PRIORITY_MAIL_INTERNATIONAL' => 'Priority Mail International',
            'FIRST_CLASS_MAIL_INTERNATIONAL' => 'First-Class Mail International',
        ],
    ],
]
```