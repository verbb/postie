<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;

use Craft;
use craft\web\Controller;

use Exception;
use yii\web\Response;

class ProvidersController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['check-connection'];


    // Public Methods
    // =========================================================================

    public function actionCheckConnection(): Response
    {
        $this->requirePostRequest();

        $providerHandle = $this->request->getParam('providerHandle');

        if (!$providerHandle) {
            return $this->asFailure(Craft::t('postie', 'Unknown provider: “{handle}”', ['handle' => $providerHandle]));
        }

        $provider = Postie::$plugin->getProviders()->getProviderByHandle($providerHandle);

        if (!$provider::supportsConnection()) {
            return $this->asFailure(Craft::t('postie', '“{handle}” does not support connection.', ['handle' => $providerHandle]));
        }

        // Populate the provider with settings
        $provider->setAttributes($this->request->getParam('settings'), false);

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            return $this->asJson([
                'success' => $provider->checkConnection(false),
            ]);
        } catch (Exception $e) {
            return $this->asFailure($e->getMessage());
        }
    }
}
