# TNT Australia
In order to use TNT Australia, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

### Connect to the TNT Australia API
1. Go to <a href="https://www.tnt.com/express/en_au/site/shipping-tools.html" target="_blank">TNT Australia Shipping Tools</a> and register for API access.
1. Copy the **Account Number** from TNT Australia and paste in the **Account Number** field in Postie.
1. Copy the **Username** from TNT Australia and paste in the **Username** field in Postie.
1. Copy the **Password** from TNT Australia and paste in the **Password** field in Postie.

### Services
The below service are available with TNT Australia for domestic customer destination addresses.

- 10:00 Express
- 12:00 Express
- 9:00 Express
- Sensitive Express
- Sensitive Express
- Overnight PAYU Satchel
- Overnight Express
- Road Express
- Fashion Express
- National Same day

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'tntAustralia'  => [
        'name' => 'TNT Australia',

        'settings' => [
            'accountNumber' => 'xxxxxxxxx',
            'username' => 'xxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxx',
        ],

        'services' => [
            'EX10' => '10:00 Express',
            'EX12' => '12:00 Express',
        ],
    ],
]
```