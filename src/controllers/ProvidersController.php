<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;

use Craft;
use craft\web\Controller;

use Exception;

class ProvidersController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['check-connection'];


    // Public Methods
    // =========================================================================

    public function actionCheckConnection(): \yii\web\Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $providerHandle = $request->getParam('providerHandle');

        if (!$providerHandle) {
            return $this->asErrorJson(Craft::t('postie', 'Unknown provider: “{handle}”', ['handle' => $providerHandle]));
        }

        $provider = Postie::$plugin->getProviders()->getProviderByHandle($providerHandle);

        if (!$provider->supportsConnection()) {
            return $this->asErrorJson(Craft::t('postie', '“{handle}” does not support connection.', ['handle' => $providerHandle]));
        }

        // Populate the provider with settings
        $provider->setAttributes($request->getParam('settings'), false);

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            return $this->asJson([
                'success' => $provider->checkConnection(false),
            ]);
        } catch (Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }
}
