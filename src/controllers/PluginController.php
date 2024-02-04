<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\events\ModifyShippableVariantsEvent;
use verbb\postie\models\Settings;

use Craft;
use craft\helpers\Db;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Variant;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class PluginController extends Controller
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_VARIANT_QUERY = 'modifyVariantQuery';


    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        $variants = [];

        $query = Variant::find()->limit(100);

        // Allow plugins to modify the variant query
        $event = new ModifyShippableVariantsEvent([
            'query' => $query,
        ]);

        if ($this->hasEventHandlers(self::EVENT_MODIFY_VARIANT_QUERY)) {
            $this->trigger(self::EVENT_MODIFY_VARIANT_QUERY, $event);
        }

        foreach (Db::each($event->query) as $variant) {
            if (!$variant->product->type->hasDimensions) {
                continue;
            }

            if ($variant->width == 0 || $variant->height == 0 || $variant->length == 0 || $variant->weight == 0) {
                $variants[] = $variant;
            }
        }

        $providers = Postie::$plugin->getProviders()->getAllProviders();

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        return $this->renderTemplate('postie/settings', [
            'settings' => $settings,
            'variants' => $variants,
            'providers' => $providers,
            'storeLocation' => $storeLocation,
        ]);
    }
}
