# Sendle

### Connect to the Sendle API
1. Go to <a href="https://www.sendle.com/#signup-form" target="_blank">Sendle</a> and login to your account.
1. You might prefer to create a <a href="https://sandbox.sendle.com/#signup-form" target="_blank">Sandbox Sendle account</a> for testing.
1. From the **Dashboard** visit the **Settings** tab from the sidebar. Click on the **Integrations** tab.
1. Copy the **Sendle ID** from Sendle and paste in the **Sendle ID** field in Postie.
1. Copy the **API Key** from Sendle and paste in the **API Key** field in Postie.

### Services
Sendle doesn't offer a set list of services for you to enable or disable as required. Services are automatically returned based on the matching criteria with your shipping origin and destination.

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'sendle' => [
        'name' => 'Sendle',

        'settings' => [
            'sendleId' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'apiKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
    ],
]
```