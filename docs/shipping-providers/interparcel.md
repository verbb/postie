# Interparcel

### Connect to the Interparcel API
1. Go to <a href="https://au.interparcel.com/business/shipping-tools" target="_blank">Interparcel</a> and request Developer API access.
1. Once approved, you'll receive an email from the Interparcel support team.
1. Copy the **API Key** from Interparcel and paste in the **API Key** field in Postie.

### Services
Interparcel doesn't offer a set list of services for you to enable or disable as required. Services are automatically returned based on the matching criteria with your shipping origin and destination.

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'interparcel' => [
        'name' => 'Interparcel',

        'settings' => [
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
]
```