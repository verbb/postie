# Origin Address

Before Postie will work correctly, you'll need to setup your **Origin Address**. This is the origin of where your products will be shipped from, and is important for shipping providers to use this in their calculations. Failure to set this up properly will result in no shipping options being shown during checkout.

You should select at least a country and set your postal code, but for best results, please fill in as many of the fields as you can.

You can either set these via the control panel, or through your configuration file.

### Control Panel

![Configuration Address](/uploads/plugins/postie/configuration-address.png)

### Configuration File

```php
'originAddress' => array(
    'company'            => '<yourCompany>',
    'streetAddressLine1' => '<yourStreetAddressLine1>',
    'streetAddressLine2' => '<yourStreetAddressLine2>',
    'city'               => '<yourCity>',
    'postalCode'         => '<yourpostalCode>',
    'state'              => '<yourState>',
    'country'            => '<yourCountry>',
),
```