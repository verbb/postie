<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\services\Providers;
use verbb\postie\services\ProviderCache;
use verbb\postie\services\Service;

use Craft;

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static Postie $plugin;


    // Static Methods
    // =========================================================================

    public static function log($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'postie');
    }

    public static function error($message): void
    {
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

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'providers' => Providers::class,
            'providerCache' => ProviderCache::class,
            'service' => Service::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('postie');
    }

}