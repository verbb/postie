<?php
namespace verbb\postie\base;

use verbb\postie\Postie;
use verbb\postie\services\Providers;
use verbb\postie\services\Rates;
use verbb\postie\services\Service;
use verbb\postie\services\Shipments;

use Craft;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use Psr\Log\LogLevel;

use Monolog\Handler\TestHandler;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Postie $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('postie');

        // Push a new handler to the regular target to keep track of current-requests
        if ($logTarget = (Craft::$app->getLog()->targets['verbb\postie\*'] ?? null)) {
            if ($logger = $logTarget->getLogger()) {
                $logger->pushHandler(new TestHandler());
            }
        }

        return [
            'components' => [
                'providers' => Providers::class,
                'rates' => Rates::class,
                'service' => Service::class,
                'shipments' => Shipments::class,
            ],
        ];
    }

    public static function debugPaneLog(string $message, array $params = []): void
    {
        $message = Craft::t('postie', $message, $params);

        if ($logTarget = (Craft::$app->getLog()->targets['verbb\postie\*'] ?? null)) {
            $logTarget->getLogger()->info($message);
        }
    }


    // Public Methods
    // =========================================================================

    public function getProviders(): Providers
    {
        return $this->get('providers');
    }

    public function getRates(): Rates
    {
        return $this->get('rates');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }

    public function getShipments(): Shipments
    {
        return $this->get('shipments');
    }

}