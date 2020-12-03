# DHL Express

### Connect to the DHL Express API
1. Go to <a href="https://xmlportal.dhl.com/login" target="_blank">DHL Express XML Developer Portal</a> and register for API access.
1. Copy the **API Username** from DHL Express and paste in the **API Username** field in Postie.
1. Copy the **API Password** from DHL Express and paste in the **API Password** field in Postie.
1. Copy the **Account Number** from DHL Express and paste in the **Account Number** field in Postie.

### Services
DHL Express doesn't offer a set list of services for you to enable or disable as required. Services are automatically returned based on the matching criteria with your shipping origin and destination.

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'dhlExpress' => [
        'name' => 'DHL Express',

        'settings' => [
            'username' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'account' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'useTestEndpoint' => true,
        ],
    ],
]
```