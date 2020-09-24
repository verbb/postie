<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\events\ModifyShippableVariantsEvent;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Variant;

class PluginController extends Controller
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_VARIANT_QUERY = 'modifyVariantQuery';


    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = Postie::$plugin->getSettings();

        $variants = [];

        $query = Variant::find();

        // Allow plugins to modify the variant query
        $event = new ModifyShippableVariantsEvent([
            'query' => $query,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_VARIANT_QUERY)) {
            $this->trigger(self::EVENT_MODIFY_VARIANT_QUERY, $event);
        }

        foreach ($event->query->all() as $variant) {
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
