# Configuration

Postie allows you to define various settings from the control panel, or through a configuration file. Create a file named `postie.php` in your `craft/config` directory. As with any configuration option, this supports multi-environment options.

Data set in the configuration file will always override data set via the control panel.

```php    
return array(
    '*' => array(
        // The address you are shipping from
        'originAddress' => array(
            'company' => 'Apple',
            'streetAddressLine1' => 'One Infinite Loop',
            'streetAddressLine2' => '',
            'city' => 'Cupertino',
            'postalCode' => '95014',
            'state' => 'CA',
            'country' => 'US',
        ),

        // Disable cache
        'disableCache' => true,

        // List of shipping providers and their services you want to make available
        'providers' => array(
            'australiaPost' => array(
                'name' => 'Australia Post',

                // etc
            ),
        ),
    ),
);
```

### Configuration options

- `originAddress` - your origin postal address from where products ship. See [Origin Address](docs:setup-configuration/origin-address)
- `disableCache` - Postie will cache all requests to the provider, but can be annoying for local testing. Set this to `true` to disable cached responses from the provider.
- `providers` - options set for each provider using their handle. See [Providers](docs:setup-configuration/providers)

For an example of all the options available, navigate to the Postie plugin folder (`craft/plugins/postie`) and look at the file `config.example.php`.