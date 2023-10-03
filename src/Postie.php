<?php
namespace verbb\postie;

use verbb\postie\base\PluginTrait;
use verbb\postie\behaviors\OrderShipmentsBehavior;
use verbb\postie\debug\PostiePanel;
use verbb\postie\events\ModifyShippableVariantsEvent;
use verbb\postie\helpers\ProjectConfigHelper;
use verbb\postie\models\Settings;
use verbb\postie\services\Providers;
use verbb\postie\variables\PostieVariable;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Address;
use craft\events\DefineBehaviorsEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\services\ProjectConfig;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\Plugin as Commerce;
use craft\commerce\services\ShippingMethods;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;

use yii\base\Event;

class Postie extends Plugin
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_VARIANT_QUERY = 'modifyVariantQuery';


    // Static Methods
    // =========================================================================

    public static function setOrderShippingAddress(Order $order): void
    {
        // $order->shippingAddress = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'TAS'], $order);
    }

    public static function getStoreShippingAddress(): Address
    {
        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();
        // $storeLocation = TestingHelper::getTestAddress('AU', ['administrativeArea' => 'VIC']);

        return $storeLocation;
    }


    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.2.5';
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

        $this->_registerComponents();
        $this->_registerLogTarget();
        $this->_registerVariables();
        $this->_registerEventHandlers();
        $this->_registerProjectConfigEventListeners();
        $this->_registerPermissions();
        $this->_registerDebugPanels();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
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
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getPluginName();

        return $navItem;
    }

    public function getInvalidVariants(?int $limit = 50): array
    {
        $variants = [];

        $query = Variant::find()->limit($limit);

        // Allow plugins to modify the variant query
        $event = new ModifyShippableVariantsEvent([
            'query' => $query,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_VARIANT_QUERY)) {
            $this->trigger(self::EVENT_MODIFY_VARIANT_QUERY, $event);
        }

        foreach (Db::each($event->query) as $variant) {
            if (!$variant->product->type->hasDimensions) {
                continue;
            }

            if ($variant->width == 0 || $variant->height == 0 || $variant->length == 0 || $variant->weight == 0) {
                $variants[] = $variant;
            }
        }

        return $variants;
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
            $event->rules['postie'] = 'postie/settings';
            $event->rules['postie/settings'] = 'postie/settings';
            $event->rules['postie/settings/general'] = 'postie/settings';
            $event->rules['postie/settings/products'] = 'postie/settings/products';
            $event->rules['postie/settings/providers'] = 'postie/providers';
            $event->rules['postie/settings/providers/new'] = 'postie/providers/edit';
            $event->rules['postie/settings/providers/edit/<providerId:\d+>'] = 'postie/providers/edit';
            $event->rules['postie/settings/shipping-methods/<providerHandle:{handle}>/<serviceHandle:{handle}>'] = 'postie/shipping-methods/edit';
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
        // Register shipping methods with Commerce
        Event::on(ShippingMethods::class, ShippingMethods::EVENT_REGISTER_AVAILABLE_SHIPPING_METHODS, [$this->getService(), 'registerShippingMethods']);

        // Store the chosen rate when the order is complete, so we have more information. We also need to cache any chosen
        // rate picked during checkout, so we can pick up once completed
        Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, [$this->getRates(), 'handleCompletedOrder']);
        Event::on(Order::class, Order::EVENT_AFTER_SAVE, [$this->getRates(), 'handleOrderSave']);

        // Add shipments tab to order edit page.
        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function(TemplateEvent $event) {
            $userService = Craft::$app->getUser();

            if ($userService->checkPermission('postie-viewShipments') || $userService->checkPermission('postie-createShipments')) {
                if ($event->template === 'commerce/orders/_edit') {
                    $event->variables['tabs']['order-shipments'] = [
                        'label' => 'Shipments',
                        'url' => '#shipmentsTab',
                        'class' => null,
                    ];
                }
            }
        });

        // Uses order edit template hook to inject order shipments.
        Craft::$app->getView()->hook('cp.commerce.order.content', function(&$context) {
            return Craft::$app->getView()->renderTemplate('postie/shipments', $context);
        });

        // Add shipments behavior to access shipments like $order->shipments.
        Event::on(Order::class, Model::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
            $event->behaviors[] = OrderShipmentsBehavior::class;
        });
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[] = [
                'heading' => Craft::t('postie', $this->getSettings()->pluginName),
                'permissions' => [
                    'postie-viewShipments' => ['label' => Craft::t('postie', 'View order shipments')],
                    'postie-createShipments' => ['label' => Craft::t('postie', 'Create order shipments')],
                    // 'postie-deleteShipments' => ['label' => Craft::t('postie', 'Delete order shipments')],
                ],
            ];
        });
    }

    private function _registerProjectConfigEventListeners(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $providersService = $this->getProviders();
        $projectConfigService
            ->onAdd(Providers::CONFIG_PROVIDERS_KEY . '.{uid}', [$providersService, 'handleChangedProvider'])
            ->onUpdate(Providers::CONFIG_PROVIDERS_KEY . '.{uid}', [$providersService, 'handleChangedProvider'])
            ->onRemove(Providers::CONFIG_PROVIDERS_KEY . '.{uid}', [$providersService, 'handleDeletedProvider']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['postie'] = ProjectConfigHelper::rebuildProjectConfig();
        });
    }

    private function _registerDebugPanels(): void
    {
        Event::on(Application::class, Application::EVENT_BEFORE_REQUEST, static function() {
            $module = Craft::$app->getModule('debug');
            $user = Craft::$app->getUser()->getIdentity();

            if (!$module || !$user || !Craft::$app->getConfig()->getGeneral()->devMode) {
                return;
            }

            $pref = Craft::$app->getRequest()->getIsCpRequest() ? 'enableDebugToolbarForCp' : 'enableDebugToolbarForSite';
            if (!$user->getPreference($pref)) {
                return;
            }

            $module->panels['postie'] = new PostiePanel([
                'id' => 'postie',
                'module' => $module,
            ]);
        });
    }
}
