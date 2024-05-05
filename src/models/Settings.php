<?php
namespace verbb\postie\models;

use Craft;
use craft\base\Model;

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
