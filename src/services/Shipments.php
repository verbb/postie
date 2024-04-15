<?php
namespace verbb\postie\services;

use verbb\postie\Postie;
use verbb\postie\events\ShipmentEvent;
use verbb\postie\models\Rate;
use verbb\postie\models\Shipment;
use verbb\postie\records\Shipment as ShipmentRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\Db;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

class Shipments extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_SHIPMENT = 'beforeSaveShipment';
    public const EVENT_AFTER_SAVE_SHIPMENT = 'afterSaveShipment';
    public const EVENT_BEFORE_DELETE_SHIPMENT = 'beforeDeleteShipment';
    public const EVENT_AFTER_DELETE_SHIPMENT = 'afterDeleteShipment';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_shipments = null;


    // Public Methods
    // =========================================================================

    public function getShipmentById(int $id): ?Shipment
    {
        return $this->_shipments()->firstWhere('id', $id);
    }

    public function getShipmentByUid(string $uid): ?Shipment
    {
        return $this->_shipments()->firstWhere('uid', $uid, true);
    }

    public function getShipmentsByOrderId(int $orderId): array
    {
        return $this->_shipments()->where('orderId', $orderId)->all();
    }

    public function saveShipment(Shipment $shipment, bool $runValidation = true): bool
    {
        $isNewShipment = !$shipment->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SHIPMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SHIPMENT, new ShipmentEvent([
                'shipment' => $shipment,
                'isNew' => $isNewShipment,
            ]));
        }

        if ($runValidation && !$shipment->validate()) {
            Craft::info('Shipment not saved due to validation error.', __METHOD__);
            return false;
        }

        $shipmentRecord = $this->_getShipmentRecord($shipment->id);
        $shipmentRecord->orderId = $shipment->orderId;
        $shipmentRecord->providerHandle = $shipment->providerHandle;
        $shipmentRecord->trackingNumber = $shipment->trackingNumber;
        $shipmentRecord->lineItems = $shipment->lineItems;
        $shipmentRecord->labels = $shipment->labels;
        $shipmentRecord->response = $shipment->response;
        $shipmentRecord->errors = $shipment->errors;

        // Save the record
        $shipmentRecord->save(false);

        // Now that we have an ID, save it on the model
        if ($isNewShipment) {
            $shipment->id = $shipmentRecord->id;
        }

        $this->_shipments = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SHIPMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SHIPMENT, new ShipmentEvent([
                'shipment' => $shipment,
                'isNew' => $isNewShipment,
            ]));
        }

        return true;
    }

    public function deleteShipmentById(int $shipmentId): bool
    {
        $shipment = $this->getShipmentById($shipmentId);

        if (!$shipment) {
            return false;
        }

        return $this->deleteShipment($shipment);
    }

    public function deleteShipment(Shipment $shipment): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SHIPMENT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SHIPMENT, new ShipmentEvent([
                'shipment' => $shipment,
            ]));
        }

        Db::delete(ShipmentRecord::tableName(), [
            'id' => $shipment->id,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SHIPMENT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SHIPMENT, new ShipmentEvent([
                'shipment' => $shipment,
            ]));
        }

        return true;
    }

    public function lodgeShipment(Shipment $shipment, Order $order, Rate $rate): bool
    {
        $provider = $rate->getProvider();

        if (!$provider) {
            return false;
        }

        $labelResponse = $provider->getLabels($order, $rate->service);

        if ($labelResponse->errors) {
            $shipment->addErrors($labelResponse->errors);

            return false;
        }

        $shipment->response = $labelResponse->response;

        foreach ($labelResponse->labels as $label) {
            $shipment->trackingNumber = $label->trackingNumber;

            $shipment->labels = [
                'id' => $label->labelId,
                'data' => $label->labelData,
                'mime' => $label->labelMime,
            ];

            if (!$this->saveShipment($shipment)) {
                return false;
            }
        }

        // Move the order to either "Shipped" or "Partially Shipped"
        if (!$this->getUnshippedLineItems($order)) {
            $orderStatus = Postie::$plugin->getSettings()->getShippedOrderStatus();
        } else {
            $orderStatus = Postie::$plugin->getSettings()->getPartiallyShippedOrderStatus();
        }

        if ($orderStatus) {
            $order->orderStatusId = $orderStatus->id;

            Craft::$app->getElements()->saveElement($order);
        }

        return true;
    }

    public function getLineItems(Order $order): array
    {
        $lineItems = [];

        foreach ($order->getLineItems() as $lineItem) {
            if ($qty = $this->getShippableQty($lineItem)) {
                $lineItems[] = [
                    'id' => $lineItem->id,
                    'title' => $lineItem->description ?? $lineItem->getDescription(),
                    'qty' => $qty,
                    'maxQty' => $qty,
                ];
            }
        }

        return $lineItems;
    }

    public function getUnshippedLineItems(Order $order): array
    {
        $lineItems = [];

        foreach (Commerce::getInstance()->getLineItems()->getAllLineItemsByOrderId($order->id) as $lineItem) {
            if ($this->getShippableQty($lineItem) > 0) {
                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }

    public function getShippableQty(LineItem $lineItem): int
    {
        $order = $lineItem->getOrder();

        $shipments = $this->getShipmentsByOrderId($order->id);

        $quantity = $lineItem->qty;

        foreach ($shipments as $shipment) {
            foreach ($shipment->lineItems as $lineItemId => $lineItemQty) {
                if ((string)$lineItemId === (string)$lineItem->id) {
                    $quantity -= $lineItemQty;
                }
            }
        }

        return $quantity;
    }


    // Private Methods
    // =========================================================================

    private function _shipments(): MemoizableArray
    {
        if (!isset($this->_shipments)) {
            $shipments = [];

            foreach ($this->_createShipmentsQuery()->all() as $result) {
                $shipments[] = new Shipment($result);
            }

            $this->_shipments = new MemoizableArray($shipments);
        }

        return $this->_shipments;
    }

    private function _createShipmentsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'orderId',
                'providerHandle',
                'trackingNumber',
                'lineItems',
                'labels',
                'response',
                'errors',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from([ShipmentRecord::tableName()]);
    }

    private function _getShipmentRecord(int|string|null $id): ShipmentRecord
    {
        /** @var ShipmentRecord $shipment */
        if ($id && $shipment = ShipmentRecord::find()->where(['id' => $id])->one()) {
            return $shipment;
        }

        return new ShipmentRecord();
    }
}
