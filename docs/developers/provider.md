# Provider
You can register your own Provider to add support for other carriers, or even extend an existing Provider.

```php
use modules\MyProvider;

use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterProviderTypesEvent $event) {
    $event->providerTypes[] = MyProvider::class;
});
```

## Example
Postie uses the [Shippy](https://github.com/verbb/shippy) package for all provider logic. As such, you should first become familiar with how creating a custom carrier in Shippy works first. Then, you can add support for your Shippy Carrier as a Postie Provider.

For our example, let's use the fictional `Wakanda Post` carrier for the rest of this guide. This provider needs an `apiKey` setting to authenticate with the API. Your provider may have different requirements.

Let's start by creating a very simple Shippy Carrier class. Again, refer to the [Shippy docs](https://github.com/verbb/shippy) for more.

Create the following class in `modules/WakandaPostCarrier.php`.

```php
namespace modules;

use verbb\shippy\carriers\AbstractCarrier;
use verbb\shippy\models\HttpClient;
use verbb\shippy\models\Rate;
use verbb\shippy\models\RateResponse;
use verbb\shippy\models\Request;
use verbb\shippy\models\Response;
use verbb\shippy\models\Shipment;

use Illuminate\Support\Arr;

class WakandaPostCarrier extends AbstractCarrier
{
    public static function getName(): string
    {
        return 'Wakanda Post';
    }

    public static function getWeightUnit(Shipment $shipment): string
    {
        return 'kg';
    }

    public static function getDimensionUnit(Shipment $shipment): string
    {
        return 'cm';
    }

    public static function getServiceCodes(): array
    {
        return [
            'PARCEL_REGULAR' => 'Parcel Post',
            'PARCEL_EXPRESS' => 'Express Post',
        ];
    }

    protected ?string $apiKey = null;

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): WakandaPostCarrier
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getRates(Shipment $shipment): ?RateResponse
    {
        $this->validate('apiKey');

        $payload = [
            // ... Construct the payload to the sent to the API
        ];

        // Create a Shippy Rate Request to fetch
        $request = new Request([
            'method' => 'POST',
            'endpoint' => 'rates',
            'payload' => [
                'json' => $payload,
            ],
        ]);

        // Fetch the raw rates from the API, and parse as JSON
        $data = $this->fetchRates($request, function(Response $response) {
            return $response->json();
        });

        $rates = [];

        // Translate the rate information from the API to Shippt Rates
        foreach (Arr::get($data, 'services', []) as $service) {
            $rates[] = new Rate([
                'carrier' => $this,
                'response' => $service,
                'serviceName' => Arr::get($service, 'services_name', ''),
                'serviceCode' => Arr::get($service, 'services_id', ''),
                'rate' => Arr::get($service, 'price', 0),
            ]);
        }

        return new RateResponse([
            'response' => $data,
            'rates' => $rates,
        ]);
    }

    public function getHttpClient(): HttpClient
    {
        return new HttpClient([
            'base_uri' => 'https://wakandapost.wk/api/v2/',
            'headers' => [
                'API-KEY' => $this->getApiKey(),
            ],
        ]);
    }
}
```

Here, we've created a Shippy Carrier class that uses their (fictional) API endpoint to fetch rates. The logic of this class is up to you to implement the action fetching of the rates.

Next, connect the Shippy Carrier to a new Postie Provider as `modules/WakandaPost.php`

```php
namespace modules;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\UrlHelper;

class WakandaPost extends Provider
{
    public static function displayName(): string
    {
        return Craft::t('postie', 'Wakanda Post');
    }

    public static function getCarrierClass(): string
    {
        return WakandaPostCarrier::class;
    }

    public ?string $apiKey = null;

    public function getApiKey(): ?string
    {
        return App::parseEnv($this->apiKey);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['apiKey'] = $this->getApiKey();

        return $config;
    }
}
```

You can see the bulk of the logic for fetching rates resides in the `WakandaPostCarrier` class, and the `WakandaPost` class acts as the connector between Postie and Shippy.

