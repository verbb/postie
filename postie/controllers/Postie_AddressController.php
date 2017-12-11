<?php

namespace Craft;


class Postie_AddressController extends BaseController
{
    public function actionIndex(array $variables = array())
    {
        if (empty($variables['address'])) {
            $variables['address'] = PostieHelper::getAddressService()->getAddress();
        }
        $variables['providers'] = PostieHelper::getService()->getRegisteredProviders();

        $countries = craft()->commerce_countries->getAllCountries();
        $variables['countries'] = array(Craft::t('Select a country')) + \CHtml::listData($countries, 'iso', 'name');

        $states = craft()->commerce_states->getAllStates();
        $cid2state = [];

        foreach ($states as $state) {
            $countryIso = craft()->commerce_countries->getCountryById($state->countryId)->iso;
            $cid2state += [$countryIso => []];

            if (count($cid2state[$countryIso]) == 0) {
                $cid2state[$countryIso][null] = "";
            }

            $cid2state[$countryIso][$state->abbreviation] = $state->name;
        }
        
        $variables['states'] = $cid2state;

        $this->renderTemplate('postie/settings/address', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();
        $model = PostieHelper::getAddressService()->getAddress();

        $model->company = craft()->request->getParam('company');
        $model->streetAddressLine1 = craft()->request->getParam('streetAddressLine1');
        $model->streetAddressLine2 = craft()->request->getParam('streetAddressLine2');
        $model->city = craft()->request->getParam('city');
        $model->state = craft()->request->getParam('state');
        $model->postalCode = craft()->request->getParam('postalCode');
        $model->country = craft()->request->getParam('country');
        
        if ($model->validate() && PostieHelper::getAddressService()->saveAddress($model)) {
            craft()->userSession->setNotice(Craft::t('Origin address saved.'));
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save origin address: {alert}', ['alert' => implode($model->getAllErrors(), ' ')]));
        }

        // Send the model back to the template
        craft()->urlManager->setRouteVariables(['address' => $model]);
    }
}