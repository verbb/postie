<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\services\Providers;
use verbb\postie\services\Service;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

use Psr\Log\LogLevel;

use Monolog\Handler\TestHandler;

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

    public static function debugPaneLog(string $message, array $params = []): void
    {
        $message = Craft::t('postie', $message, $params);

        if ($logTarget = (Craft::$app->getLog()->targets['postie'] ?? null)) {
            $logTarget->getLogger()->info($message);
        }
    }


    // Public Methods
    // =========================================================================

    public function getProviders(): Providers
    {
        return $this->get('providers');
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
            'service' => Service::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('postie', [
            // Allow debug level for compatibility with Shippy
            'level' => LogLevel::DEBUG,
        ]);

        // Push a new handler to the regular target to keep track of current-requests
        if ($logTarget = (Craft::$app->getLog()->targets['postie'] ?? null)) {
            $logTarget->getLogger()->pushHandler(new TestHandler());
        }
    }

}