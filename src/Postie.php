<?php
namespace verbb\postie;

use verbb\postie\base\PluginTrait;
use verbb\postie\models\Settings;
use verbb\postie\variables\PostieVariable;
use verbb\postie\twigextensions\Extension;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\services\ShippingMethods;

use yii\base\Event;

class Postie extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '2.0.0';
    public $hasCpSettings = true;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerTwigExtensions();
        $this->_registerVariables();
        $this->_registerCommerceEventListeners();
        
        $this->hasCpSection = $this->getSettings()->hasCpSection;
    }

    public function getPluginName()
    {
        return Craft::t('postie', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('postie/settings'));
    }

    public function getCpNavItem()
    {
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getPluginName();

        return $navItem;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerTwigExtensions()
    {
        Craft::$app->view->registerTwigExtension(new Extension);
    }

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'postie/settings/shipping-methods/<providerHandle:{handle}>/<serviceHandle:{handle}>' => 'postie/shipping-methods/edit',
                'postie/settings' => 'postie/plugin/settings',
            ]);
        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('postie', PostieVariable::class);
        });
    }

    private function _registerCommerceEventListeners()
    {
        Event::on(ShippingMethods::class, ShippingMethods::EVENT_REGISTER_AVAILABLE_SHIPPING_METHODS, [$this->getService(), 'registerShippingMethods']);
    }
}
