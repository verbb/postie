<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\services\Providers;
use verbb\postie\services\ProviderCache;
use verbb\postie\services\Service;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static Postie $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('postie', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'postie');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('postie', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'postie');
    }


    // Public Methods
    // =========================================================================

    public function getProviders(): Providers
    {
        return $this->get('providers');
    }

    public function getProviderCache(): ProviderCache
    {
        return $this->get('providerCache');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'providers' => Providers::class,
            'providerCache' => ProviderCache::class,
            'service' => Service::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('postie');
    }

}