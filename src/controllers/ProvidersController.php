<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\base\ProviderInterface;
use verbb\postie\models\MissingProvider;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use Exception;

class ProvidersController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $providers = Postie::$plugin->getProviders()->getAllProviders();

        return $this->renderTemplate('postie/settings/providers', compact('providers'));
    }

    public function actionEdit(int $providerId = null, ProviderInterface $provider = null): Response
    {
        $providersService = Postie::$plugin->getProviders();

        $registeredProviders = $providersService->getRegisteredProviders();

        $missingProviderPlaceholder = null;

        if ($provider === null) {
            $firstProviderType = ArrayHelper::firstValue($registeredProviders);

            if ($providerId !== null) {
                $provider = $providersService->getProviderById($providerId);

                if ($provider === null) {
                    throw new NotFoundHttpException('Provider not found');
                }

                if ($provider instanceof MissingProvider) {
                    $missingProviderPlaceholder = $provider->getPlaceholderHtml();
                    $provider = $provider->createFallback($firstProviderType);
                }
            } else {
                $provider = $providersService->createProvider($firstProviderType);
            }
        }

        // Make sure the selected provider class is in there
        if (!in_array(get_class($provider), $registeredProviders, true)) {
            $registeredProviders[] = get_class($provider);
        }

        $providerInstances = [];
        $providerTypeOptions = [];

        foreach ($registeredProviders as $class) {
            $providerInstances[$class] = $providersService->createProvider($class);

            $providerTypeOptions[] = [
                'value' => $class,
                'label' => $class::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($providerTypeOptions, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        $isNewProvider = !$provider->id;

        if ($isNewProvider) {
            $title = Craft::t('postie', 'Create a new provider');
        } else {
            $title = trim($provider->name) ?: Craft::t('postie', 'Edit provider');
        }

        $baseUrl = 'postie/settings/providers';
        $continueEditingUrl = 'postie/settings/providers/edit/{id}';
        $storeLocation = Commerce::getInstance()->getStore()->getStore()->getLocationAddress();

        return $this->renderTemplate('postie/settings/providers/_edit', [
            'provider' => $provider,
            'isNewProvider' => $isNewProvider,
            'providerTypes' => $registeredProviders,
            'providerTypeOptions' => $providerTypeOptions,
            'missingProviderPlaceholder' => $missingProviderPlaceholder,
            'providerInstances' => $providerInstances,
            'baseUrl' => $baseUrl,
            'continueEditingUrl' => $continueEditingUrl,
            'title' => $title,
            'storeLocation' => $storeLocation,
        ]);
    }

    public function actionSave(): ?Response
    {
        $savedProvider = null;
        $this->requirePostRequest();

        $providersService = Postie::$plugin->getProviders();
        $type = $this->request->getParam('type');
        $providerId = (int)$this->request->getParam('id');

        $settings = $this->request->getParam('types.' . $type, []);

        if ($providerId) {
            $savedProvider = $providersService->getProviderById($providerId);

            if (!$savedProvider) {
                throw new BadRequestHttpException("Invalid provider ID: $providerId");
            }

            // Be sure to merge with any existing settings, but make sure we also check if it's the same
            // type. If we're changing the type of provider, that would bleed incorrect settings
            // Have we changed type? Wipe the settings
            if ($type === get_class($savedProvider)) {
                // Be sure to merge with any existing settings
                $settings = array_merge($savedProvider->settings, $settings);
            }
        }

        $providerData = $this->_getProviderFromPost();
        $providerData['sortOrder'] = $savedProvider->sortOrder ?? null;
        $providerData['uid'] = $savedProvider->uid ?? null;

        $provider = $providersService->createProvider($providerData);

        if (!$providersService->saveProvider($provider)) {
            $this->setFailFlash(Craft::t('postie', 'Couldn’t save provider.'));

            // Send the provider back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'provider' => $provider,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('postie', 'Provider saved.'));

        return $this->redirectToPostedUrl($provider);
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $providersIds = Json::decode($this->request->getRequiredParam('ids'));
        Postie::$plugin->getProviders()->reorderProviders($providersIds);

        return $this->asJson(['success' => true]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $providersId = $request->getRequiredParam('id');

        Postie::$plugin->getProviders()->deleteProviderById($providersId);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        $this->setSuccessFlash(Craft::t('postie', 'Provider deleted.'));

        return $this->redirectToPostedUrl();
    }

    public function actionTestRates(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getRequiredParam('handle');
        $payload = $request->getRequiredParam('rateTest');

        // Test payload validation
        if (!$this->_validateTestPayload($payload)) {
            return $this->asFailure('Please provide all required values.');
        }

        $provider = Postie::$plugin->getProviders()->getProviderByHandle($handle);

        // Populate the provider with the updated settings from POST
        $providerData = $this->_getProviderFromPost();

        ArrayHelper::remove($providerData, 'type');
        $settings = array_merge($providerData, ArrayHelper::remove($providerData, 'settings'));

        Craft::configure($provider, $providerData);

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            $rates = $provider->getTestRates($payload);

            // Format any errors better, as these are assumed to be for multi-carriers
            if ($rates->getErrors()) {
                foreach ($rates->getErrors() as $error) {
                    $rates->setErrors($error);
                }
            }

            return $this->asJson($rates);
        } catch (Exception $e) {
            return $this->asFailure($e->getMessage());
        }
    }


    // Private Methods
    // =========================================================================

    private function _getProviderFromPost(): array
    {
        $providerId = (int)$this->request->getParam('id');
        $type = $this->request->getParam('type');
        $settings = $this->request->getParam('types.' . $type, []);
        $services = ArrayHelper::remove($settings, 'services') ?? [];
        $boxSizes = ArrayHelper::remove($settings, 'boxSizes') ?? [];

        return [
            'id' => $providerId ?: null,
            'name' => $this->request->getParam('name'),
            'handle' => $this->request->getParam('handle'),
            'type' => $type,
            'enabled' => (bool)$this->request->getParam('enabled'),
            'isProduction' => (bool)$this->request->getParam('isProduction'),
            'markUpRate' => (float)$this->request->getParam('markUpRate'),
            'markUpBase' => (float)$this->request->getParam('markUpBase'),
            'restrictServices' => $this->request->getParam('restrictServices'),
            'services' => $services,
            'packingMethod' => $this->request->getParam('packingMethod'),
            'boxSizes' => $boxSizes,
            'settings' => $settings,
        ];
    }

    private function _validateTestPayload(array $payload): bool
    {
        $attributes = [
            'from.addressLine1',
            'from.locality',
            'from.postalCode',
            'from.administrativeArea',
            'from.countryCode',

            'to.addressLine1',
            'to.locality',
            'to.postalCode',
            'to.administrativeArea',
            'to.countryCode',

            'width',
            'length',
            'height',
            'weight',
        ];

        foreach ($attributes as $attribute) {
            if (!ArrayHelper::getValue($payload, $attribute)) {
                return false;
            }
        }

        return true;
    }
}
