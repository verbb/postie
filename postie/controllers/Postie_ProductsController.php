<?php

namespace Craft;


class Postie_ProductsController extends BaseController
{
    /**
     * @throws Exception
     * @throws HttpException
     */
    public function actionIndex()
    {
        $variables['providers'] = PostieHelper::getService()->getRegisteredProviders();

        $variants = [];

        /** @var Commerce_VariantModel $variant */
        foreach (craft()->elements->getCriteria('Commerce_Variant')->find() as $variant) {
            if ($variant->width == 0 || $variant->height == 0 || $variant->length == 0 || $variant->weight == 0) {
                $variants[] = $variant;
            }
        }
        $variables['variants'] = $variants;

        $this->renderTemplate('postie/settings/products', $variables);
    }
}