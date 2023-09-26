<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\base\Provider;
use verbb\postie\base\ProviderInterface;
use verbb\postie\events\ProviderEvent;
use verbb\postie\events\RegisterProviderTypesEvent;
use verbb\postie\providers as registeredproviders;
use verbb\postie\models\MissingProvider;
use verbb\postie\records\Provider as ProviderRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\base\UnknownPropertyException;

use Throwable;
use craft\commerce\records\ShippingRuleCategory;
use verbb\postie\models\ShippingMethod;

class Providers extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_PROVIDER_TYPES = 'registerProviderTypes';
    public const EVENT_BEFORE_SAVE_PROVIDER = 'beforeSaveProvider';
    public const EVENT_AFTER_SAVE_PROVIDER = 'afterSaveProvider';
    public const EVENT_BEFORE_DELETE_PROVIDER = 'beforeDeleteProvider';
    public const EVENT_BEFORE_APPLY_PROVIDER_DELETE = 'beforeApplyProviderDelete';
    public const EVENT_AFTER_DELETE_PROVIDER = 'afterDeleteProvider';
    public const CONFIG_PROVIDERS_KEY = 'postie.providers';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_providers = null;
    private ?array $_overrides = null;


    // Public Methods
    // =========================================================================

    public function getRegisteredProviders(): array
    {
        $providerTypes = [
            registeredproviders\AustraliaPost::class,
            registeredproviders\Bring::class,
            registeredproviders\CanadaPost::class,
            registeredproviders\Colissimo::class,
            registeredproviders\Fastway::class,
            registeredproviders\FedEx::class,
            registeredproviders\FedExFreight::class,
            registeredproviders\Interparcel::class,
            registeredproviders\NewZealandPost::class,
            registeredproviders\PostNL::class,
            registeredproviders\RoyalMail::class,
            registeredproviders\Sendle::class,
            registeredproviders\TNTAustralia::class,
            registeredproviders\UPS::class,
            registeredproviders\USPS::class,
        ];

        $event = new RegisterProviderTypesEvent([
            'providerTypes' => $providerTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_PROVIDER_TYPES, $event);

        return $event->providerTypes;
    }

    public function getAllProviders(): array
    {
        return $this->_providers()->all();
    }

    public function getAllEnabledProviders(): array
    {
        return ArrayHelper::where($this->getAllProviders(), 'enabled', true);
    }

    public function getProviderById(int $providerId): ?ProviderInterface
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'id', $providerId);
    }

    public function getProviderByUid(string $providerUid): ?ProviderInterface
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'uid', $providerUid);
    }

    public function getProviderByHandle(string $handle): ?ProviderInterface
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'handle', $handle, true);
    }

    public function createProviderConfig(ProviderInterface $provider): array
    {
        return [
            'name' => $provider->name,
            'handle' => $provider->handle,
            'type' => get_class($provider),
            'enabled' => $provider->getEnabled(false),
            'sortOrder' => (int)$provider->sortOrder,
            'isProduction' => $provider->isProduction(false),
            'markUpRate' => $provider->markUpRate,
            'markUpBase' => $provider->markUpBase,
            'restrictServices' => $provider->restrictServices,
            'services' => $provider->services,
            'packingMethod' => $provider->packingMethod,
            'boxSizes' => $provider->boxSizes,
            'settings' => ProjectConfigHelper::packAssociativeArrays($provider->getSettings()),
        ];
    }

    public function saveProvider(ProviderInterface $provider, bool $runValidation = true): bool
    {
        $isNewProvider = $provider->getIsNew();

        // Fire a 'beforeSaveProvider' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PROVIDER)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PROVIDER, new ProviderEvent([
                'provider' => $provider,
                'isNew' => $isNewProvider,
            ]));
        }

        if (!$provider->beforeSave($isNewProvider)) {
            return false;
        }

        if ($runValidation && !$provider->validate()) {
            Postie::log('Provider not saved due to validation error.');

            return false;
        }

        if ($isNewProvider) {
            $provider->uid = StringHelper::UUID();
            
            $provider->sortOrder = (new Query())
                    ->from(['{{%postie_providers}}'])
                    ->max('[[sortOrder]]') + 1;
        } else if (!$provider->uid) {
            $provider->uid = Db::uidById('{{%postie_providers}}', $provider->id);
        }

        $configPath = self::CONFIG_PROVIDERS_KEY . '.' . $provider->uid;
        $configData = $this->createProviderConfig($provider);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$provider->handle}” provider");

        if ($isNewProvider) {
            $provider->id = Db::idByUid('{{%postie_providers}}', $provider->uid);
        }

        return true;
    }

    public function handleChangedProvider(ConfigEvent $event): void
    {
        $providerUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $providerRecord = $this->_getProviderRecord($providerUid, true);
            $isNewProvider = $providerRecord->getIsNewRecord();

            $settings = $data['settings'] ?? [];

            $providerRecord->name = $data['name'];
            $providerRecord->handle = $data['handle'];
            $providerRecord->type = $data['type'];
            $providerRecord->enabled = $data['enabled'];
            $providerRecord->sortOrder = $data['sortOrder'];
            $providerRecord->isProduction = $data['isProduction'];
            $providerRecord->markUpRate = $data['markUpRate'];
            $providerRecord->markUpBase = $data['markUpBase'];
            $providerRecord->restrictServices = $data['restrictServices'];
            $providerRecord->services = $data['services'] ?? [];
            $providerRecord->packingMethod = $data['packingMethod'];
            $providerRecord->boxSizes = $data['boxSizes'] ?? [];
            $providerRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($settings);
            $providerRecord->uid = $providerUid;

            // Save the provider
            if ($wasTrashed = (bool)$providerRecord->dateDeleted) {
                $providerRecord->restore();
            } else {
                $providerRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_providers = null;

        $provider = $this->getProviderById($providerRecord->id);
        $provider->afterSave($isNewProvider);

        // Fire an 'afterSaveProvider' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PROVIDER)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PROVIDER, new ProviderEvent([
                'provider' => $this->getProviderById($providerRecord->id),
                'isNew' => $isNewProvider,
            ]));
        }
    }

    public function reorderProviders(array $providerIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%postie_providers}}', $providerIds);

        foreach ($providerIds as $providerOrder => $providerId) {
            if (!empty($uidsByIds[$providerId])) {
                $providerUid = $uidsByIds[$providerId];
                $projectConfig->set(self::CONFIG_PROVIDERS_KEY . '.' . $providerUid . '.sortOrder', $providerOrder + 1, "Reorder provider");
            }
        }

        return true;
    }

    public function createProvider(mixed $config): ProviderInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        $handle = $config['handle'] ?? null;
        $settings = $config['settings'] ?? [];

        // Allow config settings to override source settings
        if ($handle) {
            if ($configOverrides = $this->getProviderOverrides($handle)) {
                $config['settings'] = array_merge($settings, $configOverrides);
            }
        }

        try {
            $provider = ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (UnknownPropertyException $e) {
            throw $e;
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $provider = new MissingProvider($config);
        }

        return $provider;
    }

    public function deleteProviderById(int $providerId): bool
    {
        $provider = $this->getProviderById($providerId);

        if (!$provider) {
            return false;
        }

        return $this->deleteProvider($provider);
    }

    public function deleteProvider(ProviderInterface $provider): bool
    {
        // Fire a 'beforeDeleteProvider' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PROVIDER)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PROVIDER, new ProviderEvent([
                'provider' => $provider,
            ]));
        }

        if (!$provider->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_PROVIDERS_KEY . '.' . $provider->uid, "Delete the “{$provider->handle}” provider");

        return true;
    }

    public function handleDeletedProvider(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $providerRecord = $this->_getProviderRecord($uid);

        if ($providerRecord->getIsNewRecord()) {
            return;
        }

        $provider = $this->getProviderById($providerRecord->id);

        // Fire a 'beforeApplyProviderDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_PROVIDER_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_PROVIDER_DELETE, new ProviderEvent([
                'provider' => $provider,
            ]));
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $provider->beforeApplyDelete();

            // Delete the provider
            $db->createCommand()
                ->softDelete('{{%postie_providers}}', ['id' => $providerRecord->id])
                ->execute();

            $provider->afterDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_providers = null;

        // Fire an 'afterDeleteProvider' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PROVIDER)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PROVIDER, new ProviderEvent([
                'provider' => $provider,
            ]));
        }
    }

    public function getProviderOverrides(string $handle): array
    {
        if ($this->_overrides === null) {
            $this->_overrides = Craft::$app->getConfig()->getConfigFromFile('postie');
        }

        return $this->_overrides['providers'][$handle] ?? [];
    }

    public function getShippingMethodForService(ProviderInterface $provider, string $handle): ShippingMethod
    {
        $overrides = $provider->services[$handle] ?? [];

        $shippingMethod = new ShippingMethod();
        $shippingMethod->handle = $handle;
        $shippingMethod->provider = $provider;
        $shippingMethod->name = $overrides['name'] ?? '';
        $shippingMethod->enabled = $overrides['enabled'] ?? true;

        // Also sort out saved shipping categories
        if (isset($overrides['shippingCategories'])) {
            $ruleCategories = [];

            foreach ($overrides['shippingCategories'] as $key => $ruleCategory) {
                $ruleCategories[$key] = new ShippingRuleCategory($ruleCategory);
                $ruleCategories[$key]->shippingCategoryId = $key;
            }

            $shippingMethod->shippingMethodCategories = $ruleCategories;
        }

        return $shippingMethod;
    }


    // Private Methods
    // =========================================================================

    private function _providers(): MemoizableArray
    {
        if (!isset($this->_providers)) {
            $providers = [];

            foreach ($this->_createProviderQuery()->all() as $result) {
                $providers[] = $this->createProvider($result);
            }

            $this->_providers = new MemoizableArray($providers);
        }

        return $this->_providers;
    }

    private function _createProviderQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'type',
                'enabled',
                'sortOrder',
                'isProduction',
                'markUpRate',
                'markUpBase',
                'restrictServices',
                'services',
                'packingMethod',
                'boxSizes',
                'settings',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%postie_providers}}'])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getProviderRecord(string $uid, bool $withTrashed = false): ProviderRecord
    {
        $query = $withTrashed ? ProviderRecord::findWithTrashed() : ProviderRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new ProviderRecord();
    }
}
