<?php
namespace verbb\postie\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Postie';
    public bool $enableCaching = true;
    public bool $enableRouteCheck = true;

    public array $routesChecks = [
        '/{cpTrigger}/commerce/orders/\d+',
        '/shop/shipping',
        '/shop/checkout/shipping',
    ];


    // Public Methods
    // =========================================================================

    public function hasMatchedRoute(): bool
    {
        foreach ($this->routesChecks as $url) {
            $url = str_replace(['{cpTrigger}'], [Craft::$app->getConfig()->getGeneral()->cpTrigger], $url);
            $url = str_replace('/', '\/', $url);
            $path = explode('?', Craft::$app->getRequest()->url)[0];

            if (preg_match('/^' . $url . '$/', $path, $matches)) {
                return true;
            }
        }

        return false;
    }

}
