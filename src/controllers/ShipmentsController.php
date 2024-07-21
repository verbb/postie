<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\Shipment;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\Response;

class ShipmentsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionShipmentModal(): ?Response
    {
        $rateId = $this->request->getRequiredParam('rateId');
        $orderId = $this->request->getRequiredParam('orderId');

        if (!$order = Commerce::getInstance()->getOrders()->getOrderById($orderId)) {
            return $this->asFailure('Unable to find Commerce Order for ' . $orderId);
        }

        if (!$rate = Postie::$plugin->getRates()->getRateById($rateId)) {
            return $this->asFailure('Unable to find Postie Rate for ' . $rateId);
        }

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('postie/shipments/_modal', [
                'order' => $order,
                'lineItems' => Postie::$plugin->getShipments()->getLineItems($order),
                'rate' => $rate,
            ]),
        ]);
    }

    public function actionCreateShipment(): ?Response
    {
        $this->requirePostRequest();

        $rateId = $this->request->getRequiredParam('rateId');
        $orderId = $this->request->getRequiredParam('orderId');
        $lineItems = $this->request->getRequiredParam('lineItems');

        if (!$order = Commerce::getInstance()->getOrders()->getOrderById($orderId)) {
            return $this->asFailure('Unable to find Commerce Order for ' . $orderId);
        }

        if (!$rate = Postie::$plugin->getRates()->getRateById($rateId)) {
            return $this->asFailure('Unable to find Postie Rate for ' . $rateId);
        }

        $lineItemObjects = [];

        foreach ($lineItems as $lineItemId => $qty) {
            if ((int)$qty && $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId)) {
                // Be sure to update the qty with what we've picked
                $lineItem->qty = (int)$qty;

                $lineItemObjects[] = $lineItem;
            }
        }

        $shipment = new Shipment([
            'orderId' => $order->id,
            'providerHandle' => $rate->providerHandle,
            'lineItems' => $lineItemObjects,
        ]);

        if (!Postie::$plugin->getShipments()->lodgeShipment($shipment, $order, $rate)) {
            return $this->asFailure(Json::encode($shipment->getErrors()));
        }

        return $this->asJson(['success' => true]);
    }

    public function actionDownloadLabels(): ?Response
    {
        $shipmentUid = $this->request->getRequiredParam('shipment');

        if (!$shipment = Postie::$plugin->getShipments()->getShipmentByUid($shipmentUid)) {
            return $this->asFailure('Unable to find Postie Shipment for ' . $shipmentUid);
        }

        $data = $shipment->labels['data'] ?? null;
        $mime = $shipment->labels['mime'] ?? 'application/pdf';

        if (!$data) {
            return $this->asFailure('Invalid label data for ' . $shipmentUid);
        }

        $extension = explode('/', $mime)[1] ?? 'pdf';
        $filePath = Craft::$app->getPath()->getTempPath() . '/' . StringHelper::UUID() . '.' . $extension;
        file_put_contents($filePath, base64_decode($data));

        return $this->response->sendFile($filePath, null, [
            'mimeType' => $mime,
        ]);
    }
}
