<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\ShippingMethod;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Variant;
use craft\commerce\records\ShippingRuleCategory;

class ShippingMethodsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionEdit($providerHandle, $serviceHandle)
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

    public function actionSave()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $providerHandle = $request->getBodyParam('providerHandle');
        $name = $request->getBodyParam('name');
        $handle = $request->getBodyParam('handle');
        $enabled = $request->getBodyParam('enabled');
        $shippingCategories = $request->getBodyParam('shippingCategories');

        $projectConfig = Craft::$app->getProjectConfig();

        $data = [
            'name' => $name,
            'handle' => $handle,
            'enabled' => $enabled,
            'shippingCategories' => $shippingCategories,
        ];

        $projectConfig->set('plugins.postie.settings.providers.' . $providerHandle . '.services.' . $handle, $data);

        return $this->redirectToPostedUrl();
    }
}
