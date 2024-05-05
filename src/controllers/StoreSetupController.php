<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\Settings;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\Response;

class StoreSetupController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        $storeLocation = Postie::$plugin->getService()->getPrimaryStoreLocation();

        return $this->renderTemplate('postie/store-setup', [
            'settings' => $settings,
            'storeLocation' => $storeLocation,
            'variants' => Postie::$plugin->getInvalidVariants(),
        ]);
    }
}
