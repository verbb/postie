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

        return [
            'components' => [
                'providers' => Providers::class,
                'rates' => Rates::class,
                'service' => Service::class,
                'shipments' => Shipments::class,
            ],
        ];
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