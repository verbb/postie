# Custom Providers

A provider connects Postie to a shipping carrier’s API to fetch rates and labels. Create a custom provider to add a carrier Postie doesn’t support, or to extend an existing provider.

Postie uses the [Shippy](https://github.com/verbb/shippy) package for all provider logic, so a custom provider has two parts. A Shippy carrier class talks to the carrier’s API, and a Postie provider class connects that carrier to Postie and holds its settings. Refer to the [Shippy docs](https://github.com/verbb/shippy) for more on creating a carrier.

## Create the Provider Class

This example uses a fictional `Wakanda Post` carrier, which needs an `apiKey` setting to authenticate with its API. Your carrier may have different requirements.

Start with the Shippy carrier class in `modules/WakandaPostCarrier.php`.

```php
namespace modules\sitemodule;

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

The carrier uses the (fictional) API endpoint to fetch rates and translate them into Shippy rates. How you fetch and translate rates depends on the carrier’s API.

Next, connect the carrier to a Postie provider in `modules/WakandaPost.php`.

```php
namespace modules\sitemodule;

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

Most of the logic for fetching rates lives in the `WakandaPostCarrier` class, and the `WakandaPost` class connects Postie and Shippy.

## Register the Provider

Postie finds providers through the `registerProviderTypes` event. Register your class from your module or plugin’s `init()` method.

```php
use modules\sitemodule\MyProvider;
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\services\Providers;
use yii\base\Event;

Event::on(Providers::class, Providers::EVENT_REGISTER_PROVIDER_TYPES, function(RegisterProviderTypesEvent $event) {
    $event->providerTypes[] = MyProvider::class;
});
```

## Methods

Your provider class extends `verbb\postie\base\Provider`, which handles the common provider settings, box packing and rate markup. These are the methods a custom provider implements or commonly overrides.

::: reference
### `displayName()`

**Returns:** `string`

Returns the name of this provider, shown in the control panel.
:::

::: reference
### `getCarrierClass()`

**Returns:** `string`

Returns the class name of the Shippy carrier this provider uses. Every provider must implement this static method.
:::

::: reference
### `getCarrierConfig()`

**Returns:** `array`

Returns the configuration passed to the carrier class when Postie creates it. Call the parent method and add your own settings, such as an API key.
:::

::: reference
### `defineRules()`

**Returns:** `array`

Returns the validation rules for this provider’s settings. Call the parent method and add rules for your own settings.
:::

::: reference
### `getSettingsHtml()`

**Returns:** `string|null`

Returns the HTML for this provider’s settings in the control panel. By default, Postie renders a template from its own `postie/providers/_includes` folder, so override this to render your own settings template.
:::

::: reference
### `defineDefaultBoxes()`

**Returns:** `array`

Returns the default boxes this provider offers for box packing. By default, a provider has no default boxes.
:::

::: reference
### `getMaxPackageWeight()`

**Arguments:** `$order` (`craft\commerce\elements\Order`), the order being packed.

**Returns:** `int|null`

Returns the maximum weight of a single package for the order, used when packing the order into a single box. Return `null` to use the order’s total weight.
:::
