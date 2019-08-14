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
class Mage_Paystation_StandardController extends Mage_Core_Controller_Front_Action
{
	protected $_order;

	public function getOrder()
	{
		if ($this->_order == null) {
		}
		return $this->_order;
	}

	protected function _expireAjax()
	{
		if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
			exit;
		}
	}

	public function getStandard()
	{
		return Mage::getSingleton('paystation/standard');
	}

	/**
	 * When a customer chooses Paystation on Checkout/Payment page
	 *
	 */
	 

	public function redirectAction()
	{
                
		$paystation = Mage::getSingleton('paystation/standard');
		$paystation->payment_success = false;
		
		$session = Mage::getSingleton('checkout/session');
		$session->setPaystationStandardQuoteId($session->getQuoteId());
		$_SESSION['quoteId'] = $session->getQuoteId();
		$session->unsQuoteId();
	
		header('location: ' . $_SESSION['redirectURL']);
	}
	
	public function notificationurlAction() 
	{
		$success = false;
		$message = '';
		$display_message = '';

		$session = Mage::getSingleton('checkout/session');
		$quote = $session ->getQuote();
		$quote_data = $quote->$_data;
		$is_multi_shipping = $quote_data ['is_multi_shipping'];
		
		$confirm =-1;
                $success = false;
                $QL_amount = '';
                $QL_EC =-1;
                
		if (isset($_SESSION['paystation_id'])) {
			$confirm = $this->transactionVerification($_SESSION['paystation_id'],$_GET['ti'], $QL_amount, $QL_merchant_session, $QL_EC);
                        //mail ('jack@face.co.nz', 'QL_amount:'.(int)$QL_amount. ' QL_merchant_session: '.$QL_merchant_session. ' ec: '.$confirm,  '');
		}
                
		if (!isset($_GET['em'])) {                           
			$_SESSION['paystation_success']=false;
			$success = false;
			$display_message = "Sorry, we couldn't find the payment information.\nTransaction ID: " . ((isset($_GET['ti']))?$_GET['ti']:'Not defined') . "\nError Code: " . ((isset($_GET['ec']))?$_GET['ec']:'Not defined');
			$message = "Payment information was not included with return URL." . 
			"\n<br />TnxID: " . ((isset($_GET['ti']))?$_GET['ti']:'Not defined') . 
			"\n<br />ErrCode: " . ((isset($_GET['ec']))?$_GET['ec']:'Not defined') . 
			"\n<br />TnxSess: " . ((isset($_GET['ms']))?$_GET['ms']:'Not defined') . 
			"\n<br />Amount: " . ((isset($_GET['am']))?$_GET['am']:'Not defined');
		}
		
		elseif ($_GET['ec']=="0" && (int)$confirm==0 && $QL_amount==$_GET['am'] && $_SESSION['paystation_ms'] == $QL_merchant_session) {
			// transaction approved ...
                        
                        
			$success = true;
			$_SESSION['paystation_success']=true;
			$msg 	 = "Payment successful.\n<br />TnxID: " . ((isset($_GET['ti']))?$_GET['ti']:'Not defined') . ".\n<br />TnxSess: " . ((isset($_GET['ms']))?$_GET['ms']:'Not defined');
			
			$session = Mage::getSingleton('checkout/session');

			//get the order number from the merchant reference
			$merchant_ref = $_GET['merchant_ref'];
			$xpl = explode ('-', $merchant_ref);
			$_SESSION['paystation_cust_email'] = $xpl[1];
			
			$orderid= Mage::getSingleton('checkout/session')->getLastRealOrderId();

			if ($orderid==NULL) {
			    $xpl = explode(':', $merchant_ref);
			    $customer_email = $xpl[0];
			    $orderid = $xpl[1];
			    $testmode = Mage::getStoreConfig('payment/paystation_standard/testmode', Mage::app()->getStore());	

		      	    if ($orderid == "test-mode" && $testmode =="1") {
				    $orderid = $xpl[2];
			    }
			}

			$order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
			
			if ($_SESSION['paystation_multishipping']==true) {
				
				$this->_redirect('checkout/multishipping/success');
			}
			else {
                                $postback = Mage::getStoreConfig('payment/paystation_standard/postback', Mage::app()->getStore());
                            
				if ($order && $postback=="0") {
					$this->processOrder ($order);
				}
				$this->_redirect('checkout/onepage/success');
			}
			return; // this return has not particular use - just to ensure that after redirect it doesn't do anything
			
		} else {
			// transaction failed
			$success = false;
			$display_message = $_GET['em'] . ".\nTransaction ID: " . ((isset($_GET['ti']))?$_GET['ti']:'Not defined') . "\nError Code: " . ((isset($_GET['ec']))?$_GET['ec']:'Not defined');
			
			$message 		 = "Paystation payment unsucessful. Message from gateway: " . $_GET['em'] . 
			"\n<br />TnxID: "   . ((isset($_GET['ti']))?$_GET['ti']:'Not defined') . 
			"\n<br />ErrCode: " . ((isset($_GET['ec']))?$_GET['ec']:'Not defined') . 
			"\n<br />TnxSess: " . ((isset($_GET['ms']))?$_GET['ms']:'Not defined') . 
			"\n<br />Amount: "  . ((isset($_GET['am']))?$_GET['am']:'Not defined');
		}

		if (!$success) {
			if ($is_multi_shipping==true) {
				
				$_SESSION['paystation_success']=false;
				$_SESSION['paystation_error_message'] = $display_message;
				$this->_redirect('checkout/multishipping/success');
			}
			elseif ($is_multi_shipping==false) $this->paymentError($message, $display_message);
		}
       
	}
	
	function paymentError($msg, $display_message = '') 
	{
		// if a display message has not been set then, use the order message
		if (empty($display_message)) $display_message = $msg;
		
		// cancel order
		$session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getPaystationStandardQuoteId(true));
		Mage::getModel('sales/quote')->load($session->getQuoteId())->setIsActive(true)->save();
		
                
                $postback = Mage::getStoreConfig('payment/paystation_standard/postback', Mage::app()->getStore());

                if ($session->getLastRealOrderId() && $postback=="0") {
			$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
			if ($order->getId()) {
				$order->addStatusToHistory(
					//$order->getStatus(),//continue setting current order status
					Mage_Sales_Model_Order::STATE_CANCELED,
					urldecode($msg) . ' at PayStation',
					Mage::helper('paystation')->__($msg . ' from PayStation')
				);
				$order->save();
			}
		}

		$display_message = 'Paystation payment unsuccessful. Reason: ' . "\n" . $display_message;
		$session->addError(nl2br($display_message)); 
		
		$this->_redirect('checkout/onepage/failure');

	}


	public function transactionVerification($paystationID,$transactionID, &$QL_amount, &$QL_merchant_session, &$QL_EC) {

		$transactionVerified='';
		$lookupXML=$this->quickLookup($paystationID,'ti',$transactionID);
		//$lookupXML=quickLookup($paystationID,'ms',$merchantSession);
		$p = xml_parser_create();
		xml_parse_into_struct($p, $lookupXML, $vals, $tags);
		xml_parser_free($p);
                //ob_start();
                ///echo "\r\n";
		for ($i=0;$i<count($vals);$i++) {
                    
                       $key = $vals[$i];
                        $key = $key['tag'];
                        $val = $i;
                        //var_dump ($key);
			if ($key == "PAYSTATIONERRORCODE") {
                            $transactionVerified= (int)$vals[$val]['value'];
                            $QL_EC = (int)$transactionVerified; 
                            //echo "QL_EC: "; var_dump ($QL_EC);
			}
			elseif ($key == "PURCHASEAMOUNT") { //19
                            $QL_amount= $vals[$val];
                            $QL_amount= $QL_amount ['value'];
			}
			elseif ($key == "MERCHANTSESSION") { //15
                             $QL_merchant_session= $vals[$val]['value'];
			}                          
			else {
				continue;
			}
		}
                
		return $transactionVerified;
	}	

	public function quickLookup($pi,$type,$value){
		/*
			https://www.paystation.co.nz/lookup/quick/?pi=850047&ms=123 
			- or –  
			https://www.paystation.co.nz/lookup/quick/?pi=850047&ti=0000585260-01 
		*/
			
		$url = "https://payments.paystation.co.nz/lookup/";//
		$params = "&pi=$pi&$type=$value";

                $authenticationKey  = Mage::getStoreConfig('payment/paystation_standard/hmac_key', Mage::app()->getStore());
                $hmacWebserviceName = 'paystation';
                $pstn_HMACTimestamp = time();                
                
                $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $params);
                $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
                $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;
                
		$url.= $hmacGetParams;                  
		$defined_vars = get_defined_vars();
		//use curl to get reponse	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
		curl_setopt($ch, CURLOPT_USERAGENT, $defined_vars['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$result=curl_exec ($ch);
		curl_close ($ch);
                
                
                
                $h = htmlspecialchars($result);
                
		return $result;	
	}

	function parseCode($mvalues) {
		$result='';
		for ($i=0; $i < count($mvalues); $i++) {
			if (!strcmp($mvalues[$i]["tag"],"QSIRESPONSECODE") && isset($mvalues[$i]["value"])){
				$result=$mvalues[$i]["value"];
			}
		}
		return $result;
	}
	function processOrder ($order) {

		if ($order->getId()) {
			$amount = $order->getGrandTotal();
			$order->addStatusToHistory(
				$order->getStatus(),
				urldecode($msg) . ' at Paystation',
				Mage::helper('paystation')->__($msg . ' from Paystation')
			);
			$payment = $order->getPayment();
			$payment->setTransactionId($_GET['ti'])
					->setIsTransactionClosed(0);
						
			$order->setState(
				Mage_Sales_Model_Order::STATE_PROCESSING, true, 'PayStation payment successful.');


			$st = $order->getStatus();
			

			if (method_exists($payment, 'registerCaptureNotification')) {
				$payment->registerCaptureNotification($amount);
				$order->sendNewOrderEmail();
				
	                        $order->setState(
	                                Mage_Sales_Model_Order::STATE_PROCESSING, true, 'PayStation payment successful.', true);
				$order->save();

			}
			else {
				$newOrderStatus = $order->getStatus();
				if (!$order->canInvoice()) {
				   // when order cannot create invoice, need to have some logic to take care
				   $order->addStatusToHistory(
						$order->getStatus(), // keep order status/state
						Mage::helper('paystation')->__('Error in creating an invoice', true),
						$notified = true
				   );

				} else {
				   // need to save transaction id
				   $order->getPayment()->setTransactionId($_GET['ti']);
				   
				   // need to convert from order into invoice
				   $invoice = $order->prepareInvoice();
				   $invoice->register()->pay();
				   Mage::getModel('core/resource_transaction')
					   ->addObject($invoice)
					   ->addObject($invoice->getOrder())
					   ->save();
					
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true,
					   Mage::helper('paystation')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()),
					   $notified = true
					);
					$order->save();
					$order->sendNewOrderEmail();
				}

			}
			$order->save();
		}
	}
        
}

?>
