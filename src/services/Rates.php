<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\events\RateEvent;
use verbb\postie\models\Rate;
use verbb\postie\models\ShippingMethod;
use verbb\postie\records\Rate as RateRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ModelEvent;
use craft\helpers\Db;
use craft\helpers\Json;

use yii\base\Event;

use Throwable;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class Rates extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_RATE = 'beforeSaveRate';
    public const EVENT_AFTER_SAVE_RATE = 'afterSaveRate';
    public const EVENT_BEFORE_DELETE_RATE = 'beforeDeleteRate';
    public const EVENT_AFTER_DELETE_RATE = 'afterDeleteRate';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_rates = null;


    // Public Methods
    // =========================================================================

    public function handleCompletedOrder(Event $event): void
    {
        /** @var Order $order */
        $order = $event->sender;

        // Did we have a cached shipping rate for Postie, set during the checkout?
        $cacheKey = 'postie-shipping-method:' . $order->uid;
        $shippingMethod = Craft::$app->getCache()->get($cacheKey);

        if (!$shippingMethod) {
            return;
        }

        try {
            $rate = new Rate();
            $rate->orderId = $order->id;
            $rate->providerHandle = $shippingMethod->provider->handle;
            $rate->rate = $shippingMethod->rate;
            $rate->service = $shippingMethod->handle;
            $rate->response = $shippingMethod->rateOptions;

            if (!$this->saveRate($rate)) {
                Postie::error('Unable to save rate for order {id}: “{errors}”.', [
                    'id' => $order->id,
                    'errors' => Json::encode($rate->getErrors()),
                ]);
            }
        } catch (Throwable $e) {
            Postie::error('Unable to store Postie shipping rate for order {id}: “{message}” {file}:{line}', [
                'id' => $order->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function handleOrderSave(ModelEvent $event): void
    {
        /** @var Order $order */
        $order = $event->sender;

        if (!$order->shippingMethodHandle) {
            return;
        }

        // The only real way to get the shipping method on an order now. `getShippingMethod()` doesn't include Postie's
        // and is also deprecated. We don't want to get ShippingMethodOptions either.
        $shippingMethod = Commerce::getInstance()->getShippingMethods()->getMatchingShippingMethods($order)[$order->shippingMethodHandle] ?? null;

        if (!$shippingMethod || !($shippingMethod instanceof ShippingMethod)) {
            return;
        }

        // Store the shipping method to cache, so we can pick it up when the order is completed
        $cacheKey = 'postie-shipping-method:' . $order->uid;
        Craft::$app->getCache()->set($cacheKey, $shippingMethod);
    }

    public function getRateById(int $id): ?Rate
    {
        return $this->_rates()->firstWhere('id', $id);
    }

    public function getRatesByOrderId(int $orderId): array
    {
        return $this->_rates()->where('orderId', $orderId)->all();
    }

    public function saveRate(Rate $rate, bool $runValidation = true): bool
    {
        $isNewRate = !$rate->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_RATE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_RATE, new RateEvent([
                'rate' => $rate,
                'isNew' => $isNewRate,
            ]));
        }

        if ($runValidation && !$rate->validate()) {
            Craft::info('Rate not saved due to validation error.', __METHOD__);
            return false;
        }

        $rateRecord = $this->_getRateRecord($rate->id);
        $rateRecord->orderId = $rate->orderId;
        $rateRecord->providerHandle = $rate->providerHandle;
        $rateRecord->rate = $rate->rate;
        $rateRecord->service = $rate->service;
        $rateRecord->response = $rate->response;
        $rateRecord->errors = $rate->errors;

        // Save the record
        $rateRecord->save(false);

        // Now that we have an ID, save it on the model
        if ($isNewRate) {
            $rate->id = $rateRecord->id;
        }

        $this->_rates = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_RATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_RATE, new RateEvent([
                'rate' => $rate,
                'isNew' => $isNewRate,
            ]));
        }

        return true;
    }

    public function deleteRateById(int $rateId): bool
    {
        $rate = $this->getRateById($rateId);

        if (!$rate) {
            return false;
        }

        return $this->deleteRate($rate);
    }

    public function deleteRate(Rate $rate): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_RATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_RATE, new RateEvent([
                'rate' => $rate,
            ]));
        }

        Db::delete(RateRecord::tableName(), [
            'id' => $rate->id,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_RATE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_RATE, new RateEvent([
                'rate' => $rate,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _rates(): MemoizableArray
    {
        if (!isset($this->_rates)) {
            $rates = [];

            foreach ($this->_createRatesQuery()->all() as $result) {
                $rates[] = new Rate($result);
            }

            $this->_rates = new MemoizableArray($rates);
        }

        return $this->_rates;
    }

    private function _createRatesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'orderId',
                'providerHandle',
                'rate',
                'service',
                'response',
                'errors',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from([RateRecord::tableName()]);
    }

    private function _getRateRecord(int|string|null $id): RateRecord
    {
        /** @var RateRecord $rate */
        if ($id && $rate = RateRecord::find()->where(['id' => $id])->one()) {
            return $rate;
        }

        return new RateRecord();
    }
}
