# FedEx

### Connect to the FedEx API
1. Go to <a href="https://www.fedex.com/login/web/jsp/contactInfo1.jsp" target="_blank">FedEx Developers Centre</a> and register for API access.
1. Register a <a href="https://www.fedex.com/wpor/web/jsp/commonTC.jsp" target="_blank">FedEx Web Services Production Access</a>.
1. Copy the **Account Number** from FedEx and paste in the **Account Number** field in Postie.
1. Copy the **Meter Number** from FedEx and paste in the **Meter Number** field in Postie.
1. Copy the **API Key** from FedEx and paste in the **Key** field in Postie.
1. Copy the **Password** from FedEx and paste in the **Password** field in Postie.

### Services
The below service are available with FedEx for domestic and international customer destination addresses.

- Domestic
    - FedEx 1 Day Freight
    - FedEx 2 Day
    - FedEx 2 Day AM
    - FedEx 2 DAY Freight
    - FedEx 3 Day Freight
    - FedEx Express Saver
    - FedEx First Freight
    - FedEx Freight Economy
    - FedEx Freight Priority
    - FedEx Ground
    - FedEx First Overnight
    - FedEx Priority Overnight
    - FedEx Standard Overnight
    - FedEx Ground Home Delivery
    - FedEx Same Day
    - FedEx Same Day City
    - FedEx Smart Post
    - FedEx Distance Deferred
    - FedEx Next Day Early Morning
    - FedEx Next Day Mid Morning
    - FedEx Next Day Afternoon
    - FedEx Next Day End of Day
    - FedEx Next Day Freight

- International
    - FedEx International Economy
    - FedEx International Economy Freight
    - FedEx International Economy Distribution
    - FedEx International First
    - FedEx International Priority
    - FedEx International Priority Freight
    - FedEx International Priority Distribution
    - FedEx International Priority Express
    - FedEx Europe First International Priority
    - FedEx International Distribution

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'fedEx' => [
        'name' => 'FedEx',

        'settings' => [
            'accountNumber' => 'xxxxxxxxxxxxx',
            'meterNumber' => 'xxxxxxxxxxxxx',
            'key' => 'xxxxxxxxxxxxxxxxxxxxx',
            'password' => 'xxxxxxxxxxxxxxxxxxxxx',
            'useTestEndpoint' => true,
        ],

        'services' => [
            'FEDEX_EXPRESS_SAVER' => 'Express Saver',
            'FEDEX_GROUND' => 'Ground',
            'INTERNATIONAL_ECONOMY' => 'International Economy',
            'INTERNATIONAL_PRIORITY' => 'International Priority',
        ],
    ],
]
```