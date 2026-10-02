<?php
namespace verbb\postie\controllers;

use verbb\postie\Postie;
use verbb\postie\models\Shipment;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ShipmentsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requirePermission('commerce-manageOrders');
    }

    public function actionShipmentModal(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('postie-createShipments');

        $rateId = $this->request->getRequiredBodyParam('rateId');
        $orderId = $this->request->getRequiredBodyParam('orderId');

        if (!$order = Commerce::getInstance()->getOrders()->getOrderById($orderId)) {
            return $this->asFailure('Unable to find Commerce Order for ' . $orderId);
        }

        $this->_requireOrderAccess($order);

        if (!$rate = Postie::$plugin->getRates()->getRateById($rateId)) {
            return $this->asFailure('Unable to find Postie Rate for ' . $rateId);
        }

        if ($rate->orderId !== $order->id) {
            return $this->asFailure('The selected shipping rate does not belong to this order.');
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
        $this->requirePermission('postie-createShipments');

        $rateId = $this->request->getRequiredBodyParam('rateId');
        $orderId = $this->request->getRequiredBodyParam('orderId');
        $lineItems = $this->request->getRequiredBodyParam('lineItems');

        if (!$order = Commerce::getInstance()->getOrders()->getOrderById($orderId)) {
            return $this->asFailure('Unable to find Commerce Order for ' . $orderId);
        }

        $this->_requireOrderAccess($order);

        if (!$rate = Postie::$plugin->getRates()->getRateById($rateId)) {
            return $this->asFailure('Unable to find Postie Rate for ' . $rateId);
        }

        if (!is_array($lineItems)) {
            return $this->asFailure('Select at least one line item for the shipment.');
        }

        $shipment = new Shipment([
            'orderId' => $order->id,
            'providerHandle' => $rate->providerHandle,
            'lineItems' => $lineItems,
        ]);

        if (!Postie::$plugin->getShipments()->lodgeShipment($shipment, $order, $rate)) {
            return $this->asFailure(Json::encode($shipment->getErrors()));
        }

        return $this->asJson(['success' => true]);
    }

    public function actionDownloadLabels(): ?Response
    {
        $this->_requireShipmentViewPermission();

        $shipmentUid = $this->request->getRequiredParam('shipment');

        if (!$shipment = Postie::$plugin->getShipments()->getShipmentByUid($shipmentUid)) {
            return $this->asFailure('Unable to find Postie Shipment for ' . $shipmentUid);
        }

        if (!$order = Commerce::getInstance()->getOrders()->getOrderById($shipment->orderId)) {
            return $this->asFailure('Unable to find the Commerce Order for this shipment.');
        }

        $this->_requireOrderAccess($order);

        $data = $shipment->labels['data'] ?? null;
        $mime = $shipment->labels['mime'] ?? 'application/pdf';

        if (!is_string($data) || $data === '') {
            return $this->asFailure('Invalid label data for ' . $shipmentUid);
        }

        $labelContent = base64_decode($data, true);

        if ($labelContent === false || $labelContent === '') {
            return $this->asFailure('Invalid label data for ' . $shipmentUid);
        }

        $extension = explode('/', $mime)[1] ?? 'pdf';
        $fileName = StringHelper::UUID() . '.' . $extension;

        return $this->response->sendContentAsFile($labelContent, $fileName, [
            'mimeType' => $mime,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _requireOrderAccess(Order $order): void
    {
        if (!Craft::$app->getElements()->canView($order)) {
            throw new ForbiddenHttpException('User is not authorized to view this order.');
        }
    }

    private function _requireShipmentViewPermission(): void
    {
        $userService = Craft::$app->getUser();

        if (!$userService->checkPermission('postie-viewShipments') && !$userService->checkPermission('postie-createShipments')) {
            throw new ForbiddenHttpException('User is not authorized to view shipments.');
        }
    }
}
