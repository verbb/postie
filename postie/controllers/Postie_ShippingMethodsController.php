<?php

namespace Craft;


class Postie_ShippingMethodsController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Shipping method Edit
     *
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['handle']) || empty($variables['id'])) {
            throw new HttpException(404);
        }

        // Get provider class
        $variables['provider'] = $this->_getProviderClass($variables['handle']);

        if (empty($variables['shippingMethod'])) {
            $variables['shippingMethod'] = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($variables['id']);

            // Set default shipping method attributes via provider service list
            if (!$variables['shippingMethod']->id) {
                foreach ($variables['provider']->getServiceList() as $key => $value) {
                    if ($key == $variables['id']) {
                        $shippingMethod = new Postie_ShippingMethodModel();
                        $shippingMethod->handle = $key;
                        $shippingMethod->name = $value;
                        $variables['shippingMethod'] = $shippingMethod;
                    }
                }
            }
        }

        // Set up provider api settings
        $model = PostieHelper::getProvidersService()->getProviderModelByHandle($variables['handle']);
        if (!$model->settings) {
            $settings = [];
            foreach ($variables['provider']->getAPIFields() as $field) {
                $settings[$field] = '';
            }
            $model->settings = $settings;
        }

        // Category shipping options
        $variables['categoryShippingOptions'] = [];
        $variables['categoryShippingOptions'][] = ['label' => Craft::t('Allow'), 'value' =>  Commerce_ShippingRuleCategoryRecord::CONDITION_ALLOW];
        $variables['categoryShippingOptions'][] = ['label' => Craft::t('Disallow'), 'value' =>  Commerce_ShippingRuleCategoryRecord::CONDITION_DISALLOW];
        $variables['categoryShippingOptions'][] = ['label' => Craft::t('Require'), 'value' =>  Commerce_ShippingRuleCategoryRecord::CONDITION_REQUIRE];

        $this->renderTemplate('postie/shippingmethods/_edit', $variables);
    }

    /**
     * Shipping method Save
     *
     * @throws HttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $provider = PostieHelper::getProvidersService()->getProviderModelByHandle(craft()->request->getParam('provider'));
        $shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle(craft()->request->getPost('handle'));

        $shippingMethod->providerId = $provider->id;
        $shippingMethod->handle = craft()->request->getPost('handle', $shippingMethod->handle);
        $shippingMethod->name = craft()->request->getPost('name', $shippingMethod->name);
        $shippingMethod->enabled = craft()->request->getPost('enabled') ? 1 : 0;

        // Set shipping category conditions
        $categories = [];
        foreach (craft()->request->getPost('shippingCategories') as $key => $category)
        {
            $categories[$key] = Postie_ShippingMethodCategoryModel::populateModel($category);
            $categories[$key]->shippingCategoryId = $key;
        }

        $shippingMethod->setShippingMethodCategories($categories);

        // Save it
        if (PostieHelper::getShippingMethodsService()->saveShippingMethod($shippingMethod)) {
            craft()->userSession->setNotice(Craft::t('Shipping method saved.'));
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save shipping method: {alert}', ['alert' => implode($shippingMethod->getAllErrors(), ' ')]));
        }

        // Send the models back to the template
        craft()->urlManager->setRouteVariables(['shippingMethod' => $shippingMethod]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @param string $handle
     *
     * @return \Postie\Providers\BaseProvider
     */
    private function _getProviderClass($handle)
    {
        $providers = PostieHelper::getService()->getRegisteredProviders();
        foreach ($providers as $provider) {
            if ($provider->getHandle() == $handle) {
                return $provider;
            }
        }
    }
}