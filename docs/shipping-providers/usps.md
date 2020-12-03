# USPS

### Connect to the USPS API
1. Go to <a href="https://registration.shippingapis.com" target="_blank">USPS Web Tools</a> and register for API access.
1. Copy the **Username** from USPS and paste in the **Username** field in Postie.

### Services
The below service are available with USPS for domestic and international customer destination addresses.

- Domestic
    - USPS Priority Mail Express 1-Day
    - USPS Priority Mail Express 1-Day Hold For Pickup
    - USPS Priority Mail Express 1-Day Sunday/Holiday Delivery
    - USPS Priority Mail Express 1-Day Flat Rate Envelope
    - USPS Priority Mail Express 1-Day Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail Express 1-Day Flat Rate Envelope Sunday/Holiday Delivery
    - USPS Priority Mail Express 1-Day Legal Flat Rate Envelope
    - USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Sunday/Holiday Delivery
    - USPS Priority Mail Express 1-Day Padded Flat Rate Envelope
    - USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Sunday/Holiday Delivery
    - USPS Priority Mail Express 2-Day
    - USPS Priority Mail Express 2-Day Hold For Pickup
    - USPS Priority Mail Express 2-Day Flat Rate Envelope
    - USPS Priority Mail Express 2-Day Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail Express 2-Day Legal Flat Rate Envelope
    - USPS Priority Mail Express 2-Day Legal Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail Express 2-Day Padded Flat Rate Envelope
    - USPS Priority Mail Express 2-Day Padded Flat Rate Envelope Hold For Pickup
    - USPS Priority Mail 1-Day
    - USPS Priority Mail 1-Day Large Flat Rate Box
    - USPS Priority Mail 1-Day Medium Flat Rate Box
    - USPS Priority Mail 1-Day Small Flat Rate Box
    - USPS Priority Mail 1-Day Flat Rate Envelope
    - USPS Priority Mail 1-Day Legal Flat Rate Envelope
    - USPS Priority Mail 1-Day Padded Flat Rate Envelope
    - USPS Priority Mail 1-Day Gift Card Flat Rate Envelope
    - USPS Priority Mail 1-Day Small Flat Rate Envelope
    - USPS Priority Mail 1-Day Window Flat Rate Envelope
    - USPS Priority Mail 2-Day
    - USPS Priority Mail 2-Day Large Flat Rate Box
    - USPS Priority Mail 2-Day Medium Flat Rate Box
    - USPS Priority Mail 2-Day Small Flat Rate Box
    - USPS Priority Mail 2-Day Flat Rate Envelope
    - USPS Priority Mail 2-Day Legal Flat Rate Envelope
    - USPS Priority Mail 2-Day Padded Flat Rate Envelope
    - USPS Priority Mail 2-Day Gift Card Flat Rate Envelope
    - USPS Priority Mail 2-Day Small Flat Rate Envelope
    - USPS Priority Mail 2-Day Window Flat Rate Envelope
    - USPS Priority Mail 3-Day
    - USPS Priority Mail 3-Day Large Flat Rate Box
    - USPS Priority Mail 3-Day Medium Flat Rate Box
    - USPS Priority Mail 3-Day Small Flat Rate Box
    - USPS Priority Mail 3-Day Flat Rate Envelope
    - USPS Priority Mail 3-Day Legal Flat Rate Envelope
    - USPS Priority Mail 3-Day Padded Flat Rate Envelope
    - USPS Priority Mail 3-Day Gift Card Flat Rate Envelope
    - USPS Priority Mail 3-Day Small Flat Rate Envelope
    - USPS Priority Mail 3-Day Window Flat Rate Envelope
    - USPS First-Class Mail
    - USPS First-Class Mail Stamped Letter
    - USPS First-Class Mail Metered Letter
    - USPS First-Class Mail Large Envelope
    - USPS First-Class Mail Postcards
    - USPS First-Class Mail Large Postcards
    - USPS First-Class Package Service - Retail
    - USPS Media Mail Parcel
    - USPS Library Mail Parcel
- International
    - USPS Global Express Guaranteed Envelopes
    - USPS Priority Mail Express International
    - USPS Priority Mail International
    - USPS Priority Mail International Large Flat Rate Box
    - USPS Priority Mail International Medium Flat Rate Box
    - USPS First-Class Mail International
    - USPS First-Class Package International Service

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'usps' => [
        'name' => 'USPS',

        'settings' => [
            'username' => 'xxxxxxxxxxxxx',
        ],

        'services' => [
            'PRIORITY_MAIL_EXPRESS_1_DAY' => 'Priority Mail Express',
            'PRIORITY_MAIL_1_DAY' => 'Priority Mail',
            'FIRST_CLASS_MAIL' => 'First-Class Mail',
            'PRIORITY_MAIL_INTERNATIONAL' => 'Priority Mail International',
        ],
    ],
]
```