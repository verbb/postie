# Australia Post
In order to use Australia Post, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

## Connect to the Australia Post API
1. Go to <a href="https://developers.auspost.com.au/apis/pacpcs-registration" target="_blank">Australia Post Developers website</a> and register for an API Key.
1. Copy the **API Key** from Australia Post and paste in the **API Key** field in Postie.

## Services
The below service are available with Australia Post for domestic and international customer destination addresses.

- Domestic (Parcel)
    - Parcel Post
    - Parcel Post Small Satchel
    - Parcel Post Small Satchel
    - Parcel Post Small Satchel
    - Express Post
    - Express Post Small Satchel
    - Express Post Medium (3Kg) Satchel
    - Express Post Large (5Kg) Satchel
    - Courier Post
    - Courier Post Assessed Medium Satchel
- Domestic (Letter)
    - Letter Regular Small
    - Letter Regular Medium
    - Letter Regular Large
    - Letter Regular Large (125g)
    - Letter Regular Large (250g)
    - Letter Regular Large (500g)
    - Letter Express Small
    - Letter Express Medium
    - Letter Express Large
    - Letter Express Large (125g)
    - Letter Express Large (250g)
    - Letter Express Large (500g)
    - Letter Priority Small
    - Letter Priority Medium
    - Letter Priority Large
    - Letter Priority Large (125g)
    - Letter Priority Large (250g)
    - Letter Priority Large (500g)
- International (Parcel)
    - International Standard
    - International Express
    - International Courier
    - International Economy Air
    - International Economy Sea
- International (Letter)
    - International Letter DL
    - International Letter B4
    - International Letter Express
    - International Letter Courier
    - International Letter Air Light
    - International Letter Air Medium
    - International Letter Air Heavy

## Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'australiaPost' => [
        'name' => 'Australia Post',

        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],

        'services'   => [
            'AUS_PARCEL_EXPRESS' => 'Express Post',
            'AUS_PARCEL_REGULAR' => 'Parcel Post',
        ],
    ],
]
```