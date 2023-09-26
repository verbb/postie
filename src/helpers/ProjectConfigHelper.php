<?php
namespace verbb\postie\helpers;

use verbb\postie\Postie;

class ProjectConfigHelper
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['providers'] = self::_getProvidersData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getProvidersData(): array
    {
        $data = [];

        $providersService = Postie::$plugin->getProviders();

        foreach ($providersService->getAllProviders() as $providers) {
            $data[$providers->uid] = $providersService->createProviderConfig($providers);
        }

        return $data;
    }
}
