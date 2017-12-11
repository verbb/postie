<?php

return [

    // The address you are shipping from
    'originAddress' => [
        'company'            => '<yourCompany>',
        'streetAddressLine1' => '<yourStreetAddressLine1>',
        'streetAddressLine2' => '<yourStreetAddressLine2>',
        'city'               => '<yourCity>',
        'postalCode'         => '<yourpostalCode>',
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
            'markUpRate' => '<yourmarkUpRate>',
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
            'markUpRate' => '<yourmarkUpRate>',
            'markUpBase' => '<percentage|value>',

            // List of provided services
            'services'   => [
                'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'Europe First International Priority',
                'FEDEX_1_DAY_FREIGHT'                 => '1 Day Freight',
                'FEDEX_2_DAY'                         => '2 Day',
                'FEDEX_2_DAY_AM'                      => '2 Day AM',
                'FEDEX_2_DAY_FREIGHT'                 => '2 DAY Freight',
                'FEDEX_3_DAY_FREIGHT'                 => '3 Day Freight',
                'FEDEX_EXPRESS_SAVER'                 => 'Express Saver',
                'FEDEX_FIRST_FREIGHT'                 => 'First Freight',
                'FEDEX_FREIGHT_ECONOMY'               => 'Freight Economy',
                'FEDEX_FREIGHT_PRIORITY'              => 'Freight Priority',
                'FEDEX_GROUND'                        => 'Ground',
                'FIRST_OVERNIGHT'                     => 'First Overnight',
                'GROUND_HOME_DELIVERY'                => 'Ground Home Delivery',
                'INTERNATIONAL_ECONOMY'               => 'International Economy',
                'INTERNATIONAL_ECONOMY_FREIGHT'       => 'International Economy Freight',
                'INTERNATIONAL_FIRST'                 => 'International First',
                'INTERNATIONAL_PRIORITY'              => 'International Priority',
                'INTERNATIONAL_PRIORITY_FREIGHT'      => 'International Priority Freight',
                'PRIORITY_OVERNIGHT'                  => 'Priority Overnight',
                'SMART_POST'                          => 'Smart Post',
                'STANDARD_OVERNIGHT'                  => 'Standard Overnight',
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
            'markUpRate' => '<yourmarkUpRate>',
            'markUpBase' => '<percentage|value>',

            // List of provided services shown only in the cp panel because
            // USPS get a list of available services dynamically via the API
            'services'   => [
                'DOMESTIC'      => 'Domestic',
                'INTERNATIONAL' => 'International',
            ],
        ]

        // You can add more provider and their services here
        //...
    ],
];