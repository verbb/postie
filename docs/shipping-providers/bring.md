# Bring

## Connect to the Bring API
1. Go to <a href="https://www.mybring.com/" target="_blank">Bring</a> and login to your account.
1. From the **Dashboard** visit the **Settings and API** page and generate your API keys.
1. Copy the **Username** from Bring and paste in the **Username** field in Postie.
1. Copy the **API Key** from Bring and paste in the **API Key** field in Postie.

## Services
The below service are available with Bring for domestic and international customer destination addresses.

- Klimanøytral Servicepakke
- På Døren
- Bedriftspakke
- Bedriftspakke Ekspress-Over natten
- Minipakke
- Brev
- A-Prioritert
- B-Økonomi
- Småpakker A-Post
- Småpakker B-Post
- QuickPack SameDay
- Quickpack Over Night 0900
- Quickpack Over Night 1200
- Quickpack Day Certain
- Quickpack Express Economy
- Cargo
- CarryOn Business
- CarryOn HomeShopping
- HomeDelivery Curb Side
- Bud VIP
- Bud 1 time
- Bud 2 timer
- Bud 4 timer
- Bud 6 timer
- Oil Express

## Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'bring' => [
        'name' => 'Bring',

        'settings' => [
            'username' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
]
```