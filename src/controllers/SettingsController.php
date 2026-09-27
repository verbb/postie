<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\Settings;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        return $this->renderTemplate('postie/settings/general', [
            'settings' => $settings,
        ]);
    }

}
