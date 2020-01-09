# TNT Australia

In order to use TNT Australia, you'll need to ensure you are using a valid Australian address as your store location. You'll also need to ensure your Craft Commerce default currency is set to AUD.

### How to get API access

Login to your TNT account via [TNT Australia Shipping Tools](https://www.tnt.com/express/en_au/site/shipping-tools.html) and fetch your account number, username and password.

### Services

The below service are available with TNT Australia for domestic customer destination addresses.

- `EX10`
- `EX12`
- `712`
- `717`
- `717B`
- `73`
- `75`
- `76`
- `718`
- `701`

### Configuration

Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'fastway'  => [
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