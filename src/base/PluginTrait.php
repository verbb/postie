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
    // Static Properties
    // =========================================================================

	/**
	 * @var Postie
	 */
    public static $plugin;


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

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'postie');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'postie');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'providers' => Providers::class,
            'providerCache' => ProviderCache::class,
            'service' => Service::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        BaseHelper::setFileLogging('postie');
    }

}