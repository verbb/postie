<?php

namespace Craft;

require __DIR__ . '/vendor/autoload.php';

class PostiePlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Postie');
    }

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    public function getDeveloper()
    {
        return 'Verbb';
    }

    public function getDeveloperUrl()
    {
        return 'https://verbb.io';
    }

    public function getPluginUrl()
    {
        return 'https://github.com/verbb/postie';
    }

    public function getDocumentationUrl()
    {
        return $this->getPluginUrl() . '/blob/craft-2/README.md';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/verbb/postie/craft-2/changelog.json';
    }

    /**
     * Check for requirements only after the plugin is installed (because onBeforeInstall the plugin resources are not available).
     * Redirect to welcome screen if all dependencies are installed.
     */
    public function onAfterInstall()
    {
        $dependencies = craft()->postie_plugin->checkRequirements();

        if ($dependencies) {
            craft()->runController('postie/plugin/checkRequirements');
        } else {
            craft()->request->redirect(UrlHelper::getCpUrl('postie/welcome'));
        }
    }

    public function getRequiredPlugins()
    {
        return [
            [
                'name'    => 'Commerce',
                'handle'  => 'commerce',
                'url'     => 'https://craftcommerce.com',
                'version' => '1.2.0',
            ],
        ];
    }

    /**
     * Event for third party plugins to register their own shipping provider
     *
     * @return \Postie\Providers\BaseProvider[]
     */
    public function postie_registerShippingProviders()
    {
        return PostieHelper::getService()->registerProviders();
    }

    /**
     * Commerce Event hook to register our shipping methods
     *
     * @param null $order
     *
     * @return \Postie\PostieShippingMethod[]
     */
    public function commerce_registerShippingMethods($order = null)
    {
        return PostieHelper::getService()->registerShippingMethods($order);
    }

    public function getSettingsUrl()
    {
        if (!craft()->postie_license->isLicensed()) {
            return 'postie/settings/license';
        }

        return 'postie/settings/address';
    }

    public function registerCpRoutes()
    {
        return [
            'postie/settings/license'                                    => ['action' => 'postie/license/edit'],
            'postie/settings/address'                                    => ['action' => 'postie/address'],
            'postie/settings/provider'                                   => ['action' => 'postie/providers'],
            'postie/settings/products'                                   => ['action' => 'postie/products'],
            'postie/settings/(?P<handle>\w+)'                            => ['action' => 'postie/providers/edit'],
            'postie/settings/(?P<handle>\w+)/shippingmethod/(?P<id>\w+)' => ['action' => 'postie/shippingMethods/edit'],
        ];
    }

    protected function defineSettings()
    {
        return [
            'edition' => [AttributeType::Mixed],
        ];
    }

    public function init()
    {
        if (craft()->request->isCpRequest()) {
            craft()->postie_license->ping();
        }
    }

    /**
     * Register CP alert
     *
     * @param $path
     * @param $fetch
     *
     * @return array|null
     */
    public function getCpAlerts($path, $fetch)
    {
        if ($path !== 'postie/settings/license' && !PostieHelper::getLicenseService()->isLicensed()) {
            $alert = 'You haven’t entered your Postie license key yet.';
            $alert .= '<a class="go" href="' . UrlHelper::getCpUrl('postie/settings/license') . '">Resolve</a>';

            return [$alert];
        }

        return null;
    }
}