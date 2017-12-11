<?php

namespace Postie;

use Commerce\Interfaces\ShippingRule;
use Craft\Commerce_OrderModel;
use Craft\Postie_ProviderRecord;
use Craft\PostieHelper;
use Postie\Providers\BaseProvider;

class PostieShippingRule implements ShippingRule
{
    private $_provider;
    private $_settings;
    private $_service;
    private $_name;
    private $_handle;
    private $_price;
    private $_order;

    /**
     * ShippingRule constructor
     *
     * @param BaseProvider $provider
     * @param              $service
     * @param              $price
     * @param null         $order
     */
    public function __construct(BaseProvider $provider, $service, $price, $order = null)
    {
        $this->_provider = $provider;
        $this->_settings = $provider->getProviderSettings($provider::$handle);
        $this->_service = $service;
        $this->_name = $service['name'];
        $this->_handle = $service['handle'];
        $this->_price = $price;
        $this->_order = $order;
    }

    public function getHandle()
    {
        return $this->_handle;
    }

    /**
     * Is this rule a match on the order? If false is returned, the shipping engine tries the next rule.
     *
     * @param Commerce_OrderModel $order
     *
     * @return bool
     */
    public function matchOrder(Commerce_OrderModel $order)
    {
        return $this->_price ? true : false;
    }

    /**
     * Is this shipping rule enabled for listing and selection
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return true;
    }

    /**
     * Stores this data as json on the orders shipping adjustment.
     *
     * @return mixed
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Returns the percentage rate that is multiplied per line item subtotal.
     * Zero will not make any changes.
     *
     * @return float
     */
    public function getPercentageRate()
    {
        return 0.00;
    }

    /**
     * Returns the flat rate that is multiplied per qty.
     * Zero will not make any changes.
     *
     * @return float
     */
    public function getPerItemRate()
    {
        return 0.00;
    }

    /**
     * Returns the rate that is multiplied by the line item's weight.
     * Zero will not make any changes.
     *
     * @return float
     */
    public function getWeightRate()
    {
        return 0.00;
    }

    /**
     * Returns a base shipping cost. This is added at the order level.
     * Zero will not make any changes.
     *
     * @return float
     */
    public function getBaseRate()
    {
        if (isset($this->_settings['markUpRate']) && $this->_settings['markUpRate'] != '') {

            switch ($this->_settings['markUpBase']) {

                case Postie_ProviderRecord::VALUE:
                    $this->_price += (float)$this->_settings['markUpRate'];
                    break;

                case Postie_ProviderRecord::PERCENTAGE:
                default:
                    $this->_price += $this->_price * (float)$this->_settings['markUpRate'] / 100;
                    break;
            }
        }

        return $this->_price;
    }

    /**
     * Returns a max cost this rule should ever apply.
     * If the total of your rates as applied to the order are greater than this, the baseShippingCost
     * on the order is modified to meet this max rate.
     *
     * @return float
     */
    public function getMaxRate()
    {
        return 0.00;
    }

    /**
     * Returns a min cost this rule should have applied.
     * If the total of your rates as applied to the order are less than this, the baseShippingCost
     * on the order is modified to meet this min rate.
     * Zero will not make any changes.
     *
     * @return float
     */
    public function getMinRate()
    {
        return 0.00;
    }

    /**
     * Returns a description of the rates applied by this rule;
     * Zero will not make any changes.
     *
     * @return string
     */
    public function getDescription()
    {
        return '';
    }
}