# DHL Express

### How to get API access
Register for API credentials via the [DHL Express XML Developer Portal](https://xmlportal.dhl.com/login) website. Use thse API credentials in the provider information either through the control panel, or in the configuration file.

### Services
DHL doesn't offer a set list of services for you to enable or disable as required. Services are automatically returned based on the matching criteria with your shipping origin and destination.

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