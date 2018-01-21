<?php

return [

    // The address you are shipping from
    'originAddress' => [
        'company'            => '<yourCompany>',
        'streetAddressLine1' => '<yourStreetAddressLine1>',
        'streetAddressLine2' => '<yourStreetAddressLine2>',
        'city'               => '<yourCity>',
        'postalCode'         => '<yourPostalCode>',
        'state'              => '<yourState>',
        'country'            => '<yourCountry>',
    ],

    // List of shipping provider and their service levels you want to make available
    'providers'      => [

        // Australia Post
        'australiaPost' => [

            // Provider name
            'name'       => 'Australia Post',

            // API Settings
            'settings'   => [
                'apiKey' => '<yourApiKey>',
            ],

            // Mark-Up
            'markUpRate' => '<yourMarkUpRate>',
            'markUpBase' => '<percentage|value>',

            // List of provided services
            'services'   => [
                'AUS_PARCEL_COURIER'                => 'Courier Post',
                'AUS_PARCEL_COURIER_SATCHEL_MEDIUM' => 'Courier Post Assessed Medium Satchel',
                'AUS_PARCEL_EXPRESS'                => 'Express Post',
                'AUS_PARCEL_EXPRESS_SATCHEL_500G'   => 'Express Post Small Satchel',
                'AUS_PARCEL_REGULAR'                => 'Parcel Post',
                'AUS_PARCEL_REGULAR_SATCHEL_500G'   => 'Parcel Post Small Satchel',
                'INT_PARCEL_COR_OWN_PACKAGING'      => 'Courier',
                'INT_PARCEL_EXP_OWN_PACKAGING'      => 'Express',
                'INT_PARCEL_STD_OWN_PACKAGING'      => 'Standard',
                'INT_PARCEL_AIR_OWN_PACKAGING'      => 'Economy Air',
                'INT_PARCEL_SEA_OWN_PACKAGING'      => 'Economy Sea',
            ],
        ],

        // FedEx
        'fedEx'         => [

            // Provider name
            'name'       => 'FedEx',

            // API Settings
            'settings'   => [
                'accountNumber' => '<yourAccountNumber>',
                'meterNumber'   => '<yourMeterNumber>',
                'key'           => '<yourKey>',
                'password'      => '<yourPassword>',
            ],

            // Mark-Up
            'markUpRate' => '<yourMarkUpRate>',
            'markUpBase' => '<percentage|value>',

            // List of provided services
            'services'   => [
                'FEDEX_1_DAY_FREIGHT'    => 'FedEx Domestic 1 Day Freight',
                'FEDEX_2_DAY'            => 'FedEx Domestic 2 Day',
                'FEDEX_2_DAY_AM'         => 'FedEx Domestic 2 Day AM',
                'FEDEX_2_DAY_FREIGHT'    => 'FedEx Domestic 2 DAY Freight',
                'FEDEX_3_DAY_FREIGHT'    => 'FedEx Domestic 3 Day Freight',
                'FEDEX_EXPRESS_SAVER'    => 'FedEx Domestic Express Saver',
                'FEDEX_FIRST_FREIGHT'    => 'FedEx Domestic First Freight',
                'FEDEX_FREIGHT_ECONOMY'  => 'FedEx Domestic Freight Economy',
                'FEDEX_FREIGHT_PRIORITY' => 'FedEx Domestic Freight Priority',
                'FEDEX_GROUND'           => 'FedEx Domestic Ground',
                'FIRST_OVERNIGHT'        => 'FedEx Domestic First Overnight',
                'PRIORITY_OVERNIGHT'     => 'FedEx Domestic Priority Overnight',
                'STANDARD_OVERNIGHT'     => 'FedEx Domestic Standard Overnight',
                'GROUND_HOME_DELIVERY'   => 'FedEx Domestic Ground Home Delivery',
                'SMART_POST'             => 'FedEx Domestic Smart Post',

                'INTERNATIONAL_ECONOMY'               => 'FedEx International Economy',
                'INTERNATIONAL_ECONOMY_FREIGHT'       => 'FedEx International Economy Freight',
                'INTERNATIONAL_FIRST'                 => 'FedEx International First',
                'INTERNATIONAL_PRIORITY'              => 'FedEx International Priority',
                'INTERNATIONAL_PRIORITY_FREIGHT'      => 'FedEx International Priority Freight',
                'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'FedEx Europe First International Priority',
            ],
        ],

        // USPS
        'USPS'          => [

            // Provider name
            'name'       => 'USPS',

            // API Settings
            'settings'   => [
                'username' => '<yourUsername>',
            ],

            // Mark-Up
            'markUpRate' => '<yourMarkUpRate>',
            'markUpBase' => '<percentage|value>',

            // List of provided services
            'services'   => [
                // Domestic
                'PRIORITY_MAIL_EXPRESS_1_DAY'                                                   => 'USPS Priority Mail Express 1-Day',
                'PRIORITY_MAIL_EXPRESS_1_DAY_HOLD_FOR_PICKUP'                                   => 'USPS Priority Mail Express 1-Day Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_1_DAY_SUNDAY_HOLIDAY_DELIVERY'                           => 'USPS Priority Mail Express 1-Day Sunday/Holiday Delivery',
                'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE'                                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'                => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_1_DAY_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'        => 'USPS Priority Mail Express 1-Day Flat Rate Envelope Sunday/Holiday Delivery',
                'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'                          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'          => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_1_DAY_LEGAL_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY'  => 'USPS Priority Mail Express 1-Day Legal Flat Rate Envelope Sunday/Holiday Delivery',
                'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE'                         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'         => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_1_DAY_PADDED_FLAT_RATE_ENVELOPE_SUNDAY_HOLIDAY_DELIVERY' => 'USPS Priority Mail Express 1-Day Padded Flat Rate Envelope Sunday/Holiday Delivery',

                'PRIORITY_MAIL_EXPRESS_2_DAY'                                           => 'USPS Priority Mail Express 2-Day',
                'PRIORITY_MAIL_EXPRESS_2_DAY_HOLD_FOR_PICKUP'                           => 'USPS Priority Mail Express 2-Day Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE'                        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_2_DAY_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'        => 'USPS Priority Mail Express 2-Day Flat Rate Envelope Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE'                  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_2_DAY_LEGAL_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP'  => 'USPS Priority Mail Express 2-Day Legal Flat Rate Envelope Hold For Pickup',
                'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE'                 => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope',
                'PRIORITY_MAIL_EXPRESS_2_DAY_PADDED_FLAT_RATE_ENVELOPE_HOLD_FOR_PICKUP' => 'USPS Priority Mail Express 2-Day Padded Flat Rate Envelope Hold For Pickup',

                'PRIORITY_MAIL_1_DAY'                              => 'USPS Priority Mail 1-Day',
                'PRIORITY_MAIL_1_DAY_LARGE_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Large Flat Rate Box',
                'PRIORITY_MAIL_1_DAY_MEDIUM_FLAT_RATE_BOX'         => 'USPS Priority Mail 1-Day Medium Flat Rate Box',
                'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_BOX'          => 'USPS Priority Mail 1-Day Small Flat Rate Box',
                'PRIORITY_MAIL_1_DAY_FLAT_RATE_ENVELOPE'           => 'USPS Priority Mail 1-Day Flat Rate Envelope',
                'PRIORITY_MAIL_1_DAY_LEGAL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Legal Flat Rate Envelope',
                'PRIORITY_MAIL_1_DAY_PADDED_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Padded Flat Rate Envelope',
                'PRIORITY_MAIL_1_DAY_GIFT_CARD_FLAT_RATE_ENVELOPE' => 'USPS Priority Mail 1-Day Gift Card Flat Rate Envelope',
                'PRIORITY_MAIL_1_DAY_SMALL_FLAT_RATE_ENVELOPE'     => 'USPS Priority Mail 1-Day Small Flat Rate Envelope',
                'PRIORITY_MAIL_1_DAY_WINDOW_FLAT_RATE_ENVELOPE'    => 'USPS Priority Mail 1-Day Window Flat Rate Envelope',

                'FIRST_CLASS_MAIL'                   => 'USPS First-Class Mail',
                'FIRST_CLASS_MAIL_STAMPED_LETTER'    => 'USPS First-Class Mail Stamped Letter',
                'FIRST_CLASS_MAIL_METERED_LETTER'    => 'USPS First-Class Mail Metered Letter',
                'FIRST_CLASS_MAIL_LARGE_ENVELOPE'    => 'USPS First-Class Mail Large Envelope',
                'FIRST_CLASS_MAIL_POSTCARDS'         => 'USPS First-Class Mail Postcards',
                'FIRST_CLASS_MAIL_LARGE_POSTCARDS'   => 'USPS First-Class Mail Large Postcards',
                'FIRST_CLASS_PACKAGE_SERVICE_RETAIL' => 'USPS First-Class Package Service - Retail',

                'MEDIA_MAIL_PARCEL'   => 'USPS Media Mail Parcel',
                'LIBRARY_MAIL_PARCEL' => 'USPS Library Mail Parcel',


                // International
                'USPS_GXG_ENVELOPES'                  => 'USPS Global Express Guaranteed Envelopes',
                'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'USPS Priority Mail Express International',

                'PRIORITY_MAIL_INTERNATIONAL'                      => 'USPS Priority Mail International',
                'PRIORITY_MAIL_INTERNATIONAL_LARGE_FLAT_RATE_BOX'  => 'USPS Priority Mail International Large Flat Rate Box',
                'PRIORITY_MAIL_INTERNATIONAL_MEDIUM_FLAT_RATE_BOX' => 'USPS Priority Mail International Medium Flat Rate Box',

                'FIRST_CLASS_MAIL_INTERNATIONAL'            => 'USPS First-Class Mail International',
                'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' => 'USPS First-Class Package International Service',
            ],
        ]

        // You can add more provider and their services here
        //...
    ],
];