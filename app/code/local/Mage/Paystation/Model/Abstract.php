<?php
/**
 * PayStation Payment Module For Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * http://opensource.org/licenses/osl-3.0.php
  *
 * @package    Mage_Paystation
 * @author		Gayatri S Ajith <gayatri@schogini.com>
 * @copyright   Copyright (c) 2010 Schogini (http://schogini.biz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PayStation Abstract Payment Module
 */
abstract class Mage_Paystation_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Get Paystation API Model
     *
     * @return Mage_Paystation_Model_Api_Nvp
     */
    public function getApi()
    {
        return Mage::getSingleton('paystation/api_nvp');
    }

    /**
     * Get paystation session namespace
     *
     * @return Mage_Paystation_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('paystation/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getRedirectUrl()
    {
        return $this->getApi()->getRedirectUrl();
    }

    public function getCountryRegionId()
    {
        $a = $this->getApi()->getShippingAddress();
        return $this;
    }
}