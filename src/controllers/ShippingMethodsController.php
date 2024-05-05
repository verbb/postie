<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;

use Craft;
use craft\web\Controller;

use craft\commerce\records\ShippingRuleCategory;
use yii\web\Response;

class ShippingMethodsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionEdit($providerHandle, $serviceHandle): Response
    {
        $provider = Postie::$plugin->getProviders()->getProviderByHandle($providerHandle);

        $shippingMethod = $provider->getShippingMethodByHandle($serviceHandle);

        $categoryShippingOptions = [
            ['label' => Craft::t('commerce', 'Allow'), 'value' => ShippingRuleCategory::CONDITION_ALLOW],
            ['label' => Craft::t('commerce', 'Disallow'), 'value' => ShippingRuleCategory::CONDITION_DISALLOW],
            ['label' => Craft::t('commerce', 'Require'), 'value' => ShippingRuleCategory::CONDITION_REQUIRE],
        ];

        return $this->renderTemplate('postie/settings/shipping-methods/_edit', [
            'provider' => $provider,
            'serviceHandle' => $serviceHandle,
            'shippingMethod' => $shippingMethod,
            'categoryShippingOptions' => $categoryShippingOptions,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $pluginHandle = 'postie';
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        $providerHandle = $this->request->getBodyParam('providerHandle');
        $name = $this->request->getBodyParam('name');
        $handle = $this->request->getBodyParam('handle');
        $enabled = $this->request->getBodyParam('enabled');
        $shippingCategories = $this->request->getBodyParam('shippingCategories');

        $pluginInfo = Craft::$app->plugins->getStoredPluginInfo('postie');
        $providerSettings = $pluginInfo['settings']['providers'][$providerHandle]['services'][$handle] ?? [];

        $newSettings = array_merge($providerSettings, [
            'name' => $name,
            'handle' => $handle,
            'enabled' => $enabled,
            'shippingCategories' => $shippingCategories,
        ]);

        $pluginInfo['settings']['providers'][$providerHandle]['services'][$handle] = $newSettings;

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $pluginInfo['settings'])) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
