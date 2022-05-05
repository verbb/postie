# Royal Mail
In order to use Royal Mail, you'll need to ensure you are using a valid United Kingdom address as your store location. You'll also need to ensure your Craft Commerce default currency is set to Pounds Sterling.

Royal Mail do not offer live rates via their API. Prices according to the [2020 price guide](https://www.royalmail.com/sites/royalmail.com/files/2020-02/royal-mail-our-prices-valid-from-23-march-2020.pdf).

## Services
The below service are available with Royal Mail for domestic and international customer destination addresses.

- Domestic
    - Royal Mail 1st Class
    - Royal Mail Signed For&reg; 1st Class
    - Royal Mail 2nd Class
    - Royal Mail Signed For&reg; 2nd Class
    - Royal Mail Special Delivery Guaranteed by 9am&reg;
    - Royal Mail Special Delivery Guaranteed by 1pm&reg;
    - Parcelforce Worldwide Express 9
    - Parcelforce Worldwide Express 10
    - Parcelforce Worldwide Express AM
    - Parcelforce Worldwide Express 24
    - Parcelforce Worldwide Express 48
- International
    - Royal Mail International Standard
    - Royal Mail International Tracked &amp; Signed
    - Royal Mail International Tracked
    - Royal Mail International Signed
    - Royal Mail International Economy
    - Parcelforce Worldwide Ireland Express
    - Parcelforce Worldwide Global Economy
    - Parcelforce Worldwide Global Express
    - Parcelforce Worldwide Global Priority
    - Parcelforce Worldwide Global Value

## Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'royalMail' => [
        'name' => 'Royal Mail',
    ],
]
```