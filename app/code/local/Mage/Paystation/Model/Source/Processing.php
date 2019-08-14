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
class Mage_Paystation_Model_Source_Processing extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    protected $_stateStatuses = array (
        //Mage_Sales_Model_Order::STATE_NEW,
        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        //Mage_Sales_Model_Order::STATE_PROCESSING,
        //Mage_Sales_Model_Order::STATE_COMPLETE,
        //Mage_Sales_Model_Order::STATE_CLOSED,
        //Mage_Sales_Model_Order::STATE_CANCELED,
        //Mage_Sales_Model_Order::STATE_HOLDED,
    );
}