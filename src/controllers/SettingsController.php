<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\Settings;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\Response;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        return $this->renderTemplate('postie/settings/general', [
            'settings' => $settings,
            'storeLocation' => $storeLocation,
        ]);
    }

    public function actionProducts(): Response
    {
        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();

        return $this->renderTemplate('postie/settings/products', [
            'settings' => $settings,
            'variants' => Postie::$plugin->getInvalidVariants(),
        ]);
    }

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        /* @var Settings $settings */
        $settings = Postie::$plugin->getSettings();
        $settings->setAttributes($request->getParam('settings'), false);

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(Craft::t('postie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Postie::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(Craft::t('postie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('postie', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

}
