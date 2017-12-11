<?php

namespace Craft;

class Postie_LicenseController extends BaseController
{

    // Public Methods
    // =========================================================================

    public function actionEdit()
    {
        $licenseKey = craft()->postie_license->getLicenseKey();

        $this->renderTemplate('postie/settings/license', [
            'providers'     => PostieHelper::getService()->getRegisteredProviders(),
            'hasLicenseKey' => ($licenseKey !== null),
        ]);
    }

    public function actionGetLicenseInfo()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->postie_license->getLicenseInfo());
    }

    public function actionUnregister()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->postie_license->unregisterLicenseKey());
    }

    public function actionTransfer()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->postie_license->transferLicenseKey());
    }

    public function actionUpdateLicenseKey()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $licenseKey = craft()->request->getRequiredPost('licenseKey');

        // Are we registering a new license key?
        if ($licenseKey) {
            // Record the license key locally
            try {
                craft()->postie_license->setLicenseKey($licenseKey);
            } catch (InvalidLicenseKeyException $e) {
                $this->returnErrorJson(Craft::t('The license key is invalid.'));
            }

            return $this->_sendResponse(craft()->postie_license->registerPlugin($licenseKey));
        } else {
            // Just clear our record of the license key
            craft()->postie_license->setLicenseKey(null);
            craft()->postie_license->setLicenseKeyStatus(LicenseKeyStatus::Unknown);

            return $this->_sendResponse();
        }
    }


    // Private Methods
    // =========================================================================

    private function _sendResponse($success = true)
    {
        if ($success) {
            $this->returnJson([
                'success'          => true,
                'licenseKey'       => craft()->postie_license->getLicenseKey(),
                'licenseKeyStatus' => craft()->plugins->getPluginLicenseKeyStatus('Postie'),
            ]);
        } else {
            //$this->returnErrorJson(craft()->postie_license->error);
            $this->returnErrorJson(Craft::t('An unknown error occurred.'));
        }
    }

}
