<?php
namespace verbb\postie\providers;

use verbb\postie\base\Provider;
use verbb\postie\helpers\TestingHelper;

use Craft;
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use verbb\shippy\carriers\Aramex as AramexCarrier;

class Aramex extends Provider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('postie', 'Aramex');
    }

    public static function getCarrierClass(): string
    {
        return AramexCarrier::class;
    }


    // Properties
    // =========================================================================

    public ?string $username = null;
    public ?string $password = null;
    public ?string $version = null;
    public ?string $accountNumber = null;
    public ?string $accountPin = null;
    public ?string $accountEntity = null;
    public ?string $accountCountryCode = null;
    public ?string $source = null;


    // Public Methods
    // =========================================================================

    public function getUsername(): ?string
    {
        return App::parseEnv($this->username);
    }

    public function getPassword(): ?string
    {
        return App::parseEnv($this->password);
    }

    public function getVersion(): ?string
    {
        return App::parseEnv($this->version);
    }

    public function getAccountNumber(): ?string
    {
        return App::parseEnv($this->accountNumber);
    }

    public function getAccountPin(): ?string
    {
        return App::parseEnv($this->accountPin);
    }

    public function getAccountEntity(): ?string
    {
        return App::parseEnv($this->accountEntity);
    }

    public function getAccountCountryCode(): ?string
    {
        return App::parseEnv($this->accountCountryCode);
    }

    public function getSource(): ?string
    {
        return App::parseEnv($this->source);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'password', 'version', 'accountNumber', 'accountPin', 'accountEntity'], 'required', 'when' => function($model) {
            return $model->enabled;
        }];

        return $rules;
    }

    public function getCarrierConfig(): array
    {
        $config = parent::getCarrierConfig();
        $config['username'] = $this->getUsername();
        $config['password'] = $this->getPassword();
        $config['version'] = $this->getVersion();
        $config['accountNumber'] = $this->getAccountNumber();
        $config['accountPin'] = $this->getAccountPin();
        $config['accountEntity'] = $this->getAccountEntity();
        $config['accountCountryCode'] = $this->getAccountCountryCode();
        $config['source'] = $this->getSource();

        return $config;
    }
}
