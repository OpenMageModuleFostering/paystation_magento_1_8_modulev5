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
class Mage_Paystation_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
		//$this->setTemplate('paystation/standard/form.phtml');
		
		// We need to ask only the CC type from the customer - rest of the information will be collected
		// by PayStation when we redirect to their servers. Also, the value for each card type is different
		// from the normal values in Magento. Hence, we need to specifically pass them to our custom template
		//$this->setData('cctypes', Mage::getModel('Mage_Paystation_Model_Standard')->getConfigData('cctypes'));
        parent::_construct();
    }
}