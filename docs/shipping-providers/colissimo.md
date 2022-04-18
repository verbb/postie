# Colissimo
In order to use Colissimo, you'll need to ensure you are using a valid France address as your store location. You'll also need to ensure your Craft Commerce default currency is set to Euros.

Colissimo do not offer live rates via their API. Prices according to the [2017 price guide](http://www.colissimo.fr/particuliers/envoyer_un_colis/decouvrir_loffre_colissimo/Tarifs_colissimo/Tarifs_colissimo.jsp).

## Services
The below service are available with Colissimo for domestic and international customer destination addresses.

- France
- Emballage France
- Outre-Mer
- Europe
- Economique Outre-Mer
- International
- Emballage International

## Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'colissimo' => [
        'name' => 'Colissimo',
    ],
]
```