<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Variant;

class PluginController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = Postie::$plugin->getSettings();

        $variants = [];

        foreach (Variant::find()->all() as $variant) {
            if ($variant->width == 0 || $variant->height == 0 || $variant->length == 0 || $variant->weight == 0) {
                $variants[] = $variant;
            }
        }

        $providers = Postie::$plugin->getProviders()->getAllProviders();

        $storeLocation = Commerce::getInstance()->getAddresses()->getStoreLocationAddress();

        return $this->renderTemplate('postie/settings', array(
            'settings' => $settings,
            'variants' => $variants,
            'providers' => $providers,
            'storeLocation' => $storeLocation,
        ));
    }
}
