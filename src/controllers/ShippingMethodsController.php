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

    public function actionEdit(string $providerHandle, string $serviceHandle): Response
    {
        $provider = Postie::$plugin->getProviders()->getProviderByHandle($providerHandle);

        $shippingMethod = Postie::$plugin->getProviders()->getShippingMethodForService($provider, $serviceHandle);

        $categoryShippingOptions = [
            ['label' => Craft::t('commerce', 'Allow'), 'value' => ShippingRuleCategory::CONDITION_ALLOW],
            ['label' => Craft::t('commerce', 'Disallow'), 'value' => ShippingRuleCategory::CONDITION_DISALLOW],
            ['label' => Craft::t('commerce', 'Require'), 'value' => ShippingRuleCategory::CONDITION_REQUIRE],
        ];

        return $this->renderTemplate('postie/shipping-methods/_edit', [
            'provider' => $provider,
            'serviceHandle' => $serviceHandle,
            'shippingMethod' => $shippingMethod,
            'categoryShippingOptions' => $categoryShippingOptions,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $providerHandle = $this->request->getRequiredParam('providerHandle');
        $provider = Postie::$plugin->getProviders()->getProviderByHandle($providerHandle);

        $name = $this->request->getParam('name');
        $handle = $this->request->getParam('handle');
        $enabled = $this->request->getBodyParam('enabled');
        $shippingCategories = $this->request->getParam('shippingCategories');

        $serviceSettings = $provider->services[$handle] ?? [];

        $provider->services[$handle] = array_merge($serviceSettings, [
            'name' => $name,
            'handle' => $handle,
            'enabled' => $enabled,
            'shippingCategories' => $shippingCategories,
        ]);

        if (!Postie::$plugin->getProviders()->saveProvider($provider)) {
            $this->setFailFlash(Craft::t('postie', 'Couldn’t save shipping method.'));

            return null;
        }

        return $this->redirectToPostedUrl();
    }
}
