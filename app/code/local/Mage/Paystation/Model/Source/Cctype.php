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
class Mage_Paystation_Model_Source_Cctype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'visa', 'label' => 'Visa'),
            array('value' => 'mastercard', 'label' => 'MasterCard'),
            array('value' => 'amex', 'label' => 'American Express'),
            array('value' => 'dinersclub', 'label' => 'Diners Club'),
            array('value' => 'qcard', 'label' => 'Union Pay'),
        );
    }
}