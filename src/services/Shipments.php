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
use craft\helpers\Json;

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
        $shipmentRecord->lineItems = $this->_normalizeLineItemQuantities($shipment->lineItems);
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
        if (!$order->id) {
            $shipment->addError('orderId', 'Unable to lodge a shipment without a saved order.');

            return false;
        }

        $lockName = 'postie:shipment:order:' . $order->id;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lockName, 5)) {
            $shipment->addError('orderId', 'Another shipment is currently being created for this order.');

            return false;
        }

        try {
            return $this->_lodgeShipment($shipment, $order, $rate);
        } finally {
            $mutex->release($lockName);
        }
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

        if (!$order?->id) {
            return 0;
        }

        $shipments = $this->getShipmentsByOrderId($order->id);
        $quantity = $lineItem->qty;

        foreach ($shipments as $shipment) {
            $quantity -= $shipment->lineItems[$lineItem->id] ?? 0;
        }

        return max(0, $quantity);
    }


    // Private Methods
    // =========================================================================

    private function _lodgeShipment(Shipment $shipment, Order $order, Rate $rate): bool
    {
        $this->_shipments = null;

        $lineItems = $this->_prepareLineItems($shipment, $order, $rate);

        if ($lineItems === null) {
            return false;
        }

        $provider = $rate->getProvider();

        if (!$provider) {
            $shipment->addError('providerHandle', 'Unable to find the shipping provider for this rate.');

            return false;
        }

        $labelResponse = $provider->getLabels($order, $rate->service, $lineItems);

        if (!$labelResponse) {
            $shipment->addError('labels', 'Unable to create shipping labels.');

            return false;
        }

        if ($labelResponse->errors) {
            $shipment->addErrors($labelResponse->errors);

            return false;
        }

        $shipment->response = $labelResponse->response;

        if (!$labelResponse->labels) {
            $shipment->addError('labels', 'The shipping provider did not return any labels.');

            return false;
        }

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

    private function _prepareLineItems(Shipment $shipment, Order $order, Rate $rate): ?array
    {
        if ($shipment->orderId !== $order->id || $rate->orderId !== $order->id) {
            $shipment->addError('orderId', 'The shipment and shipping rate must belong to the same order.');

            return null;
        }

        if (!$shipment->providerHandle || $shipment->providerHandle !== $rate->providerHandle) {
            $shipment->addError('providerHandle', 'The shipment provider does not match the shipping rate.');

            return null;
        }

        if (!$rate->service) {
            $shipment->addError('providerHandle', 'The shipping rate does not identify a provider service.');

            return null;
        }

        $orderLineItems = [];

        foreach ($order->getLineItems() as $lineItem) {
            if ($lineItem->id) {
                $orderLineItems[$lineItem->id] = $lineItem;
            }
        }

        $lineItemQuantities = [];

        foreach ($shipment->lineItems as $lineItemId => $quantity) {
            if ($quantity instanceof LineItem) {
                $lineItemId = $quantity->id;
                $quantity = $quantity->qty;
            }

            $lineItemId = $this->_positiveInteger($lineItemId);
            $quantity = $this->_nonNegativeInteger($quantity);

            if (!$lineItemId || $quantity === null || isset($lineItemQuantities[$lineItemId])) {
                $shipment->addError('lineItems', 'Shipment quantities must be whole numbers for unique line items.');

                return null;
            }

            if ($quantity === 0) {
                continue;
            }

            $lineItem = $orderLineItems[$lineItemId] ?? null;

            if (!$lineItem) {
                $shipment->addError('lineItems', 'A selected line item does not belong to this order.');

                return null;
            }

            if ($quantity > $this->getShippableQty($lineItem)) {
                $shipment->addError('lineItems', 'A selected quantity is greater than the remaining shippable quantity.');

                return null;
            }

            $lineItemQuantities[$lineItemId] = $quantity;
        }

        if (!$lineItemQuantities) {
            $shipment->addError('lineItems', 'Select at least one line item for the shipment.');

            return null;
        }

        $lineItems = [];

        foreach ($lineItemQuantities as $lineItemId => $quantity) {
            $lineItem = clone $orderLineItems[$lineItemId];
            $lineItem->qty = $quantity;
            $lineItem->setOrder($order);
            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    private function _normalizeLineItemQuantities(mixed $lineItems): array
    {
        if (is_string($lineItems)) {
            try {
                $lineItems = Json::decodeIfJson($lineItems);
            } catch (\Throwable) {
                return [];
            }
        }

        if (!is_array($lineItems)) {
            return [];
        }

        $quantities = [];

        foreach ($lineItems as $lineItemId => $quantity) {
            if ($quantity instanceof LineItem) {
                $lineItemId = $quantity->id;
                $quantity = $quantity->qty;
            } elseif (is_array($quantity)) {
                $lineItemId = $quantity['id'] ?? null;
                $quantity = $quantity['qty'] ?? null;
            }

            $lineItemId = $this->_positiveInteger($lineItemId);
            $quantity = $this->_positiveInteger($quantity);

            if ($lineItemId && $quantity) {
                $quantities[$lineItemId] = ($quantities[$lineItemId] ?? 0) + $quantity;
            }
        }

        return $quantities;
    }

    private function _positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!is_string($value) || !preg_match('/^[1-9]\d*$/D', $value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $value === false ? null : $value;
    }

    private function _nonNegativeInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (!is_string($value) || !preg_match('/^(?:0|[1-9]\d*)$/D', $value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        return $value === false ? null : $value;
    }

    private function _shipments(): MemoizableArray
    {
        if (!isset($this->_shipments)) {
            $shipments = [];

            foreach ($this->_createShipmentsQuery()->all() as $result) {
                $result['lineItems'] = $this->_normalizeLineItemQuantities($result['lineItems'] ?? []);
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
