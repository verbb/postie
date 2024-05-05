<?php
namespace verbb\postie;

use verbb\postie\base\PluginTrait;
use verbb\postie\models\Settings;
use verbb\postie\variables\PostieVariable;
use verbb\postie\twigextensions\Extension;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\services\ShippingMethods;

use yii\base\Event;

class Postie extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.1.1';
    public string $minVersionRequired = '2.2.7';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariables();
        $this->_registerEventHandlers();
        $this->_registerCommerceEventListeners();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        $this->hasCpSection = $this->getSettings()->hasCpSection;

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $this->hasCpSection = false;
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('postie', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('postie/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $nav = parent::getCpNavItem();
        $nav['label'] = $this->getPluginName();

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('postie', 'Settings'),
                'url' => 'postie/settings',
            ];
        }

        return $nav;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'postie/settings/shipping-methods/<providerHandle:{handle}>/<serviceHandle:{handle}>' => 'postie/shipping-methods/edit',
                'postie/settings' => 'postie/plugin/settings',
            ]);
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('postie', PostieVariable::class);
        });
    }

    private function _registerEventHandlers(): void
    {
        // Whenever we update the cart, we need to check if manual fetching of rates is set. If it is, we need to check for the
        // correct POST param, then set a session variable to save it. This is because the fetching of shipping rates isn't done
        // in the same request as the POST sent to the server. We need to temporarily store a flag is session with the okay to fetch.
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, [$this->getService(), 'onAfterSaveOrder']);

        // Prevent saving _all_ provider information to plugin settings, just the enabled ones. Specifically, weed out services
        // so we retain at least some info for other providers. Helps keep project config under control.
        Event::on(Plugins::class, Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, function(PluginEvent $event) {
            if ($event->plugin === $this) {
                $this->getService()->onBeforeSavePluginSettings($event);
            }
        });
    }

    private function _registerCommerceEventListeners(): void
    {
        Event::on(ShippingMethods::class, ShippingMethods::EVENT_REGISTER_AVAILABLE_SHIPPING_METHODS, [$this->getService(), 'registerShippingMethods']);
    }
}
