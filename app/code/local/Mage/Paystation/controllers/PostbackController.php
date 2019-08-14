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
 * @author	Jack Stinchcombe info@paystation.co.nz
 * @copyright   Copyright (c) 2014 Paystation Ltd
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * 
 */
require_once('Mage/Paystation/controllers/StandardController.php');

class Mage_Paystation_PostbackController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $xml = file_get_contents('php://input');
        $xml = simplexml_load_string($xml);
        //var_dump ($xml);

        if (!empty($xml)) {
            //echo ('<hr>!empty(xml) <br><br>');
            $errorCode = (int) $xml->ec;
            $errorMessage = $xml->em;
            $transactionId = $xml->ti;
            $cardType = $xml->ct;
            $merchantReference = $xml->merchant_ref;
            $testMode = $xml->tm;
            $merchantSession = $xml->MerchantSession;
            $usedAcquirerMerchantId = $xml->UsedAcquirerMerchantID;
            $amount = $xml->PurchaseAmount; // Note this is in cents
            $transactionTime = $xml->TransactionTime;
            $requestIp = $xml->RequestIP;

            $message = "Error Code: " . $errorCode . "<br/>";
            $message .= "Error Message: " . $errorMessage . "<br/>";
            $message .= "Transaction ID: " . $transactionId . "<br/>";
            $message .= "Card Type: " . $cardType . "<br/>";
            $message .= "Merchant Reference: " . $merchantReference . "<br/>";
            $message .= "Test Mode: " . $testMode . "<br/>";
            $message .= "Merchant Session: " . $merchantSession . "<br/>";
            $message .= "Merchant ID: " . $usedAcquirerMerchantId . "<br/>";
            $message .= "Amount: " . $amount . " (cents)<br/>";
            $message .= "Transaction Time: " . $transactionTime . "<br/>";
            $message .= "IP: " . $requestIp . "<br/>";

            $merchant_ref = $merchantReference;
            $xpl = explode(':', $merchant_ref);
            $customer_email = $xpl[0];
            $orderid = $xpl[1];
            $testmode = Mage::getStoreConfig('payment/paystation_standard/testmode', Mage::app()->getStore());

            if ($orderid == "test-mode" && $testmode == "1") {
                $orderid = $xpl[2];
            }



            //var_dump ($customer_email);


            $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);


            if ($errorCode == 0) {

                // transaction approved ...
                //var_dump ($order);

                $this->processOrder($order, $transactionId, $errorMessage);

                $s = $order->getStatusLabel();

                //var_dump ($s);
                //echo " -==-  =-=-= ";
                //var_dump ($s); 
                //var_dump ($order);

                $success = true;

                $msg = "Payment successful.\n<br />TnxID: " . $transactionId . ".\n<br />TnxSess: " . $merchantSession;


                return; // this 
            } else {
                if ($order->getId()) {
                    $order->addStatusToHistory(
                            $order->getStatus(), //continue setting current order status
                            Mage_Sales_Model_Order::STATE_CANCELED, urldecode($errorMessage) . ' at PayStation', Mage::helper('paystation')->__($errorMessage . ' from PayStation')
                    );
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                    $order->save();

                    //\echo "<hr>";
                }
            }
        }
    }

    function processOrder($order, $transactionId, $errorMessage) {
//$s = "dsffsdsd";//$order->getStatusLabel();
//                echo " ??  ??";
//var_dump ($s);
//echo "<hr>";
//
//	echo "<pre>";
//	var_dump($order);
        echo "<hr>";

        if ($order->getId()) {
            if ($order->canInvoice())
                $this->doInvoice($order);
            else {
                $amount = $order->getGrandTotal();
                $order->addStatusToHistory(
                        $order->getStatus(), urldecode($errorMessage) . ' at Paystation', Mage::helper('paystation')->__($errorMessage . ' from Paystation')
                );
                $payment = $order->getPayment();
                $payment->setTransactionId($transactionId)
                        ->setIsTransactionClosed(0);

                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true); //, Mage::helper('paystation')->__('PayStation payment successful.', true), $notified = true);
                $order->save();
            }
            //var_dump ($order);

            if (method_exists($payment, 'registerCaptureNotification')) {
                $payment->registerCaptureNotification($amount);
                $order->sendNewOrderEmail();
            } else {
                $newOrderStatus = $order->getStatus();
            }
            $order->save();
        }
    }

    function doInvoice($order) {
        if (!$order->canInvoice()) {

            // when order cannot create invoice, need to have some logic to take care
            $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    Mage::helper('paystation')->__('Error in creating an invoice', true), $notified = true
            );
        } else {
            //exit( 'invoice creation');
            // need to save transaction id
            $order->getPayment()->setTransactionId($_GET['ti']);

            // need to convert from order into invoice
            $invoice = $order->prepareInvoice();
            $invoice->register()->pay();
            Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, Mage::helper('paystation')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()), $notified = true);
            $order->save();
            $order->sendNewOrderEmail();
        }
    }

}

?>
