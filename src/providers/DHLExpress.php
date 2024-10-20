<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;

use craft\commerce\elements\Order;

use DateTime;

use verbb\shippy\carriers\DHLExpress as DHLExpressCarrier;

class DHLExpress extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'DHL Express');
    }

    public static function getCarrierClass(): string
    {
        return DHLExpressCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $accountNumber = null;
    public string|array|null $shipDate = null;
    public string|array|null $shipTime = null;

    private int $maxWeight = 70000; // 70kg


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Normalize `shipTime` - possibly a better way to do this...
        // https://github.com/verbb/postie/issues/146
        if (isset($config['shipTime']['time'])) {
            $spaces = [
                "\u{00A0}", // Non-breaking space
                "\u{2000}", // En quad
                "\u{2001}", // Em quad
                "\u{2002}", // En space
                "\u{2003}", // Em space
                "\u{2004}", // Three-per-em space
                "\u{2005}", // Four-per-em space
                "\u{2006}", // Six-per-em space
                "\u{2007}", // Figure space
                "\u{2008}", // Punctuation space
                "\u{2009}", // Thin space
                "\u{200A}", // Hair space
                "\u{202F}", // Narrow no-break space
                "\u{205F}", // Medium mathematical space
                "\u{3000}", // Ideographic space
            ];

            $config['shipTime']['time'] = str_replace($spaces, ' ', $config['shipTime']['time']);
        }

        parent::__construct($config);
    }

    public function getClientId(): ?string
    {
        return App::parseEnv($this->clientId);
    }

    public function getUsername(): ?string
    {
        return App::parseEnv($this->username);
    }

    public function getPassword(): ?string
    {
        return App::parseEnv($this->password);
    }

    public function getAccountNumber(): ?string
    {
        return App::parseEnv($this->accountNumber);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'password'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() !== self::API_TRACKING;
        }];

        $rules[] = [['clientId'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_TRACKING;
        }];

        $rules[] = [['accountNumber'], 'required', 'when' => function($model) {
            return $model->enabled && $model->getApiType() === self::API_SHIPPING;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();

        $shipDate = new DateTime($this->shipTime);

        if ($this->shipDate === 'nextDay') {
            $shipDate = $shipDate->modify('+1 day');
        }

        if ($this->shipDate === 'nextBusinessDay') {
            $shipDate = $shipDate->modify('+1 weekday');
        }

        $config['shipDateTime'] = $shipDate;

        if ($this->getApiType() === self::API_TRACKING) {
            $config['clientId'] = $this->getClientId();
        } else {
            $config['username'] = $this->getUsername();
            $config['password'] = $this->getPassword();
        }

        if ($this->getApiType() === self::API_SHIPPING) {
            $config['accountNumber'] = $this->getAccountNumber();
        }

        return $config;
    }

    public function getApiTypeOptions(): array
    {
        return [
            ['label' => Craft::t('postie', 'Rates Only'), 'value' => self::API_RATES],
            ['label' => Craft::t('postie', 'Tracking Only'), 'value' => self::API_TRACKING],
            ['label' => Craft::t('postie', 'All'), 'value' => self::API_SHIPPING],
        ];
    }

    public function getMaxPackageWeight(Order $order): ?int
    {
        return $this->maxWeight;
    }

    public function getTestingOriginAddress(): Address
    {
        return TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);
    }

    public function getTestingDestinationAddress(): Address
    {
        return TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS']);
    }
}
