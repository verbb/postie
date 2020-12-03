# PostNL
In order to use PostNL, you'll need to ensure you are using a valid Netherlands address as your store location. You'll also need to ensure your Craft Commerce default currency is set to Euros.

PostNL do not offer live rates via their API. Prices according to the [2018 price guide](http://www.postnl.nl/Images/Postal-Rates-sheet-january-2016-PostNL_tcm10-71860.pdf).

### Services
The below service are available with PostNL for domestic and international customer destination addresses.

- Brief
- Brievenbuspakje
- Pakket no Track & Trace
- Pakket
- Aangetekend
- Verzekerservice
- Betaalservice

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'postNl' => [
        'name' => 'PostNL',
    ],
]
```