<?php

namespace Craft;


class Postie_ProvidersController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Provider Edit
     *
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['handle'])) {
            throw new HttpException(404);
        }

        if (empty($variables['model'])) {
            $variables['model'] = PostieHelper::getProvidersService()->getProviderModelByHandle($variables['handle']);
        }
        $variables['providers'] = PostieHelper::getService()->getRegisteredProviders();

        // Get provider class
        $variables['provider'] = $this->_getProviderClass($variables['handle']);

        // Set up provider api settings
        if (!$variables['model']->settings) {
            $settings = [];
            foreach ($variables['provider']->getAPIFields() as $field) {
                $settings[$field] = '';
            }
            $variables['model']->settings = $settings;
        }

        // Set up shipping providers
        if (empty($variables['shippingMethods'])) {
            $shippingMethods = [];

            foreach ($variables['provider']->getServiceList() as $key => $value) {
                $shippingMethod = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($key);

                if (!$shippingMethod->id) {
                    $shippingMethod = new Postie_ShippingMethodModel();
                    $shippingMethod->handle = $key;
                    $shippingMethod->name = $value;
                }

                $shippingMethods[] = $shippingMethod;
            }

            $variables['shippingMethods'] = $shippingMethods;
        }

        $this->renderTemplate('postie/providers/_edit', $variables);
    }

    /**
     * Provider Save
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $provider = PostieHelper::getProvidersService()->getProviderModelByHandle(craft()->request->getPost('handle'));

        $provider->name = craft()->request->getPost('name', $provider->name);
        $provider->enabled = craft()->request->getPost('enabled', $provider->enabled);
        $provider->settings = craft()->request->getPost('settings', $provider->settings);
        $provider->markUpRate = craft()->request->getPost('markUpRate', $provider->markUpRate);
        $provider->markUpBase = craft()->request->getPost('markUpBase', $provider->markUpBase);

        if (PostieHelper::getProvidersService()->saveProvider($provider)) {
            craft()->userSession->setNotice(Craft::t('Provider saved.'));
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save provider: {alert}', ['alert' => implode($provider->getAllErrors(), ' ')]));
        }

        $shippingMethodModelsWithErrors = [];

        // Iterate through the list of shipping methods and store every single
        $shippingMethods = craft()->request->getPost('shippingMethods');
        if (count($shippingMethods) > 0) {
            foreach ($shippingMethods as $shippingMethodHandle => $shippingMethodValue) {

                // Create shipping method model and save the value from the check box
                /** @var Postie_ShippingMethodModel $shippingMethodModel */
                $shippingMethodModel = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($shippingMethodHandle);
                
                $shippingMethodModel->providerId = $provider->id;
                $shippingMethodModel->handle = $shippingMethodHandle;
                $shippingMethodModel->name = $shippingMethodValue['name'];
                $shippingMethodModel->enabled = $shippingMethodValue['enabled'] == 1 ? 1 : 0;

                if (!PostieHelper::getShippingMethodsService()->saveShippingMethod($shippingMethodModel)) {
                    craft()->userSession->setError(Craft::t('Couldn’t save shipping method: {alert}', ['alert' => implode($shippingMethodModel->getAllErrors(), ' ')]));
                }

                $shippingMethodModelsWithErrors[$shippingMethodModel->handle] = $shippingMethodModel;
            }
        }

        // Merge the shipping methods models with errors into all shipping methods models
        $shippingMethodsModels = [];
        $providerClass = $this->_getProviderClass($provider->handle);

        foreach ($providerClass->getServiceList() as $key => $value) {
            if (isset($shippingMethodModelsWithErrors[$key])) {
                $shippingMethodsModels[] = $shippingMethodModelsWithErrors[$key];
            } else {
                $shippingMethodsModels[] = PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($key);
            }
        }

        // Send the models back to the template
        craft()->urlManager->setRouteVariables(['model' => $provider, 'shippingMethods' => $shippingMethodsModels]);
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