<?php
namespace verbb\postie\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

use craft\commerce\Plugin as Commerce;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Postie';
    public bool $enableCaching = true;
    public bool $enableRouteCheck = true;
    public ?string $shippedOrderStatus = 'shipped';
    public ?string $partiallyShippedOrderStatus = 'partiallyShipped';

    public array $routesChecks = [
        '/{cpTrigger}/commerce/orders/\d+',
        '/{actionTrigger}/commerce/orders/refresh',
        '/shop/shipping',
        '/shop/checkout/shipping',
    ];


    // Public Methods
    // =========================================================================

    public function getEnableCaching(): bool|string
    {
        return App::parseBooleanEnv($this->enableCaching);
    }

    public function getEnableRouteCheck(): bool|string
    {
        return App::parseBooleanEnv($this->enableRouteCheck);
    }

    public function hasMatchedRoute(): bool
    {
        foreach ($this->routesChecks as $url) {
            $url = str_replace([
                '{cpTrigger}',
                '{actionTrigger}',
            ], [
                rtrim(Craft::$app->getConfig()->getGeneral()->cpTrigger, '/'),
                rtrim(Craft::$app->getConfig()->getGeneral()->actionTrigger, '/'),
            ], $url);

            // Escape slashes for regex
            $path = explode('?', Craft::$app->getRequest()->url)[0];

            if (preg_match('/' . str_replace('/', '\/', $url) . '$/', $path, $matches)) {
                return true;
            }
        }

        return false;
    }

    public function getShippedOrderStatus()
    {
        return Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($this->shippedOrderStatus);
    }

    public function getPartiallyShippedOrderStatus()
    {
        return Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($this->partiallyShippedOrderStatus);
    }

    public function getOrderStatusOptions(): array
    {
        $statuses = [
            [
                'label' => Craft::t('postie', 'Select an option'),
                'value' => '',
            ],
        ];

        $orderStatus = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses();

        foreach ($orderStatus as $orderStatus) {
            $statuses[] = ['label' => $orderStatus->name, 'value' => $orderStatus->handle];
        }

        return $statuses;
    }

}
