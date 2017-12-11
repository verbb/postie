<?php
namespace Craft;

use Postie\PostieShippingMethod;
use Postie\Providers\BaseProvider;
use Postie\Providers\AustraliaPostProvider;
use Postie\Providers\FedExProvider;
use Postie\Providers\USPSProvider;

class PostieService extends BaseApplicationComponent
{

    /**
     * Get all registered providers.
     * Calls event "registerPostieProviders" to load our own and third party providers.
     *
     * @return BaseProvider[]
     */
    public function getRegisteredProviders()
    {
        $providers = [];

        // Load registered third party providers
        if (craft()->postie_license->isLicensed()) {
            $providersToLoad = craft()->plugins->call('postie_registerShippingProviders');

            // Check if registered provider is instance of BaseProvider
            foreach ($providersToLoad as $plugin => $providerClasses) {
                foreach ($providerClasses as $providerClass) {
                    if ($providerClass && $providerClass instanceof BaseProvider) {
                        $providers[] = $providerClass;
                    }
                }
            }
        } else {
            $providers = $this->registerProviders();
        }

        return $providers;
    }

    /**
     * Register shipping methods of all registered and activated providers (including third party providers).
     * Check for shipping methods only at shipping and payment page (improved performance).
     *
     * @param null $order
     *
     * @return PostieShippingMethod[]
     */
    public function registerShippingMethods($order = null)
    {
        $methods = [];

        // @TODO Need a better way to handle requests only on shipping and payment page for performance purposes
//        if (!craft()->request->isCpRequest()
//            && craft()->request->getPath() != 'shop/checkout/shipping'
//            && craft()->request->getPath() != 'shop/checkout/payment') {
//
//            return $methods;
//        }
        
        $providers = PostieHelper::getService()->getRegisteredProviders();

        foreach ($providers as $provider) {

            // Check if Provider is enabled
            $providerModel = PostieHelper::getProvidersService()->getProviderModelByHandle($provider->getHandle());
            if (!$providerModel->isEnabled()) {
                continue;
            }

            // Get USPS/FedEx service list via API call
            if ($provider instanceof USPSProvider || $provider instanceof FedExProvider) {
                $services = $provider->getServices($order);
            } else {
                $services = $provider->getServices();
            }

            if (!$services) {
                continue;
            }

            foreach ($services as $handle => $name) {
                $rate = null;

                if ($order) {
                    $rate = $provider->getShippingRate($handle, $order);

                    // Get USPS/FedEx service name from previous api call
                    if ($provider instanceof USPSProvider || $provider instanceof FedExProvider) {
                        $name = $provider->getServiceName($handle);
                    }
                }

                $methods[] = new PostieShippingMethod($provider, ['handle' => $handle, 'name' => $name], $rate, $order);
            }
        }

        return $methods;
    }

    /**
     * @return BaseProvider[]
     */
    public function registerProviders()
    {
        return [
            new AustraliaPostProvider(),
            new FedExProvider(),
            new USPSProvider(),
        ];
    }
}
