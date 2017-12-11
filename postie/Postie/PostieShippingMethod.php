<?php
namespace Postie;

use function Craft\craft;
use Commerce\Interfaces\ShippingMethod;
use Craft\PostieHelper;
use Craft\UrlHelper;
use Postie\Providers\BaseProvider;

class PostieShippingMethod implements ShippingMethod
{
    private $_provider;
    private $_providerHandle;
    private $_service;
    private $_name;
    private $_handle;
    private $_price;
    private $_order;

    /**
     * ShippingMethod constructor
     *
     * @param BaseProvider $provider
     * @param              $service
     * @param              $price
     * @param null         $order
     */
	public function __construct(BaseProvider $provider, $service, $price, $order = null)
	{
        $this->_provider = $provider;
        $this->_providerHandle = $provider::$handle;
        $this->_service = $service;
        $this->_name = $service['name'];
        $this->_handle = $service['handle'];
        $this->_price = $price;
        $this->_order = $order;
	}

	/**
	 * Returns the type of Shipping Method.
	 * The core shipping methods have type: `Custom`. This is shown in the control panel only.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->_provider->getName();
	}

	/**
	 * Returns the ID of this Shipping Method, if it is managed by Craft Commerce.
	 *
	 * @return int|null The shipping method ID, or null if it is not managed by Craft Commerce
	 */
	public function getId()
	{
		return null;
	}

	/**
	 * Returns the unique handle of this Shipping Method.
	 *
	 * @return string
	 */
	public function getHandle()
	{
		return $this->_handle;
	}

	/**
	 * Returns the control panel URL to manage this method and it's rules.
	 * An empty string will result in no link.
	 *
	 * @return string
	 */
	public function getCpEditUrl()
	{
        return  UrlHelper::getCpUrl() . '/postie/settings/' . $this->_providerHandle;
	}

	/**
	 * Returns an array of rules that meet the `ShippingRules` interface.
	 *
	 * @return PostieShippingRule[] The array of ShippingRules
	 */
	public function getRules()
	{
		return [new PostieShippingRule($this->_provider, $this->_service, $this->_price, $this->_order)];
	}

	/**
	 * Returns the name of this Shipping Method as displayed to the customer and in the control panel.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Is this shipping method enabled for listing and selection by customers.
	 *
	 * @return bool
	 */
	public function getIsEnabled()
	{
        return (
            PostieHelper::getProvidersService()->getProviderModelByHandle($this->_providerHandle)->isEnabled()
            && PostieHelper::getShippingMethodsService()->getShippingMethodModelByHandle($this->_handle)->isEnabled()
        );
	}
}