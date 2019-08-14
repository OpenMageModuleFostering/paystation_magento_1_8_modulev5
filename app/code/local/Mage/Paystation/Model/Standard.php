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
 * 
 * Updated Jack Stinchcombe 18/11/2013
 * Updated Jack Stinchcombe 05/08/2014
 */
class Mage_Paystation_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'paystation_standard';
    protected $_formBlockType = 'paystation/standard_form';
    protected $_allowCurrencyCode = array('AUD', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'SEK', 'SGD', 'USD');
    // This payment method doesn't support capture, void or refund
    protected $_authorize = '';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;

    public function getSession() {
        return Mage::getSingleton('paystation/session');
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    public function canUseInternal() {
        return false;
    }

    public function canUseForMultishipping() {
        return true;
    }

    public function createFormBlock($name) {
        $block = $this->getLayout()->createBlock('paystation/standard_form', $name)
                ->setMethod('paystation_standard')
                ->setPayment($this->getPayment())
                ->setTemplate('paystation/standard/form.phtml');

        return $block;
    }

    /* validate the currency code is avaialable to use for paystation or not */

    public function validate() {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!in_array($currency_code, $this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('paystation')->__('Selected currency code (' . $currency_code . ') is not compatabile with PayStation'));
        }

        //if (!isset($_SESSION['redirectURL'])) $this->initiate_paystation();
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment) {
        return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment) {
        
    }

    public function canCapture() {
        return true;
    }

    public function getOrderPlaceRedirectUrl() {

        //mail('jack@face.co.nz', 'getOrderPlaceRedirectUrl()', '');

        //return Mage::getUrl('paystation/standard/redirect', array('_secure' => true));
        // set the quote id so that we can load the quote later if, we need to show the shopping cart page again.
        $session = Mage::getSingleton('checkout/session');
        $session->setPaystationStandardQuoteId($session->getQuoteId());
        $session->unsQuoteId();

        return $this->initiate_paystation();
        /* 		if (isset($_SESSION['redirectURL']) && !empty($_SESSION['redirectURL'])) {
          return $_SESSION['redirectURL'];
          }
          return null; */
    }

    // The fields that you set here are acces in Redirect.php
    // and can be sent to the external payment gateway if needed.
    // All the fields that are entered by the user on the checkout page can be accessed here.
    public function getStandardCheckoutFormFields() {

        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $quote = $this->getQuote();
        $currency_code = $quote->getBaseCurrencyCode();
        $isQuoteVirtual = $quote->getIsVirtual();
        $address = $isQuoteVirtual ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $shipping = $isQuoteVirtual ? $quote->getShippingAddress() : $quote->getBillingAddress();
        $customer_email = $address->getEmail();

        //$orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        
        if ($address->getFirstname() == '') {
            $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
            $quote = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            $currency_code = $quote->getBaseCurrencyCode();
            $isQuoteVirtual = $quote->getIsVirtual();
            $address = $isQuoteVirtual ? $quote->getBillingAddress() : $quote->getShippingAddress();
            $shipping = $isQuoteVirtual ? $quote->getShippingAddress() : $quote->getBillingAddress();
            $customer_email = $quote->getCustomerEmail();
        }

        if (!isset($shipping) || empty($shipping)) {
            $shipping = $address;
        }

        $sArr = array(
            'merchant_id' => $this->getConfigData('paystation_id'),
            'gateway_id' => $this->getConfigData('gateway_id'),
            'testmode' => $this->getConfigData('testmode'),
            'invoice' => $this->getCheckout()->getLastRealOrderId(),
            'currency_code' => $currency_code,
            'address_override' => 1,
            // billing address
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'address1' => $address->getStreet(1),
            'address2' => $address->getStreet(2),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountry(),
            'zip' => $address->getPostcode(),
            'telephone' => $address->getTelephone(),
            // shipping address
            's_first_name' => $shipping->getFirstname(),
            's_last_name' => $shipping->getLastname(),
            's_address1' => $shipping->getStreet(1),
            's_address2' => $shipping->getStreet(2),
            's_city' => $shipping->getCity(),
            's_state' => $shipping->getRegionCode(),
            's_country' => $shipping->getCountry(),
            's_zip' => $shipping->getPostcode(),
            's_telephone' => $shipping->getTelephone(),
            'email' => $customer_email,
            'cctype' => $quote->getPayment()->getCcType(),
            'order' => $order
        );

        //http://docs.magentocommerce.com/Mage_Sales/Mage_Sales_Model_Quote.htm

        $quote2 = $quote->collectTotals();
        $totals = $quote2->getTotals();

        $final_amount = $totals['grand_total']['value'];

        $sArr['final_amount'] = sprintf('%.2f', $final_amount);
        $_SESSION['paystation_amount'] = number_format($final_amount, 4);
        $_SESSION['paystation_id'] = $this->getConfigData('paystation_id');
        //var_dump ($_SESSION['paystation_amount']);

        return $sArr;
    }

    public function getPaystationUrl() {
        $url = 'https://secure.paystation.com/cgi-bin/order2/processorder1.pl';
        return $url;
    }

    public function getDebug() {
        return Mage::getStoreConfig('paystation/wps/debug_flag');
    }

    public function isInitializeNeeded() {
        return true;
    }

    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    function makePaystationSessionID($min = 8, $max = 8) {
        // seed the random number generator - straight from PHP manual
        $seed = (double) microtime() * getrandmax();
        srand($seed);

        // make a string of $max characters with ASCII values of 40-122
        $p = 0;
        $pass ="";
        while ($p < $max):
            $r = chr(123 - (rand() % 75));

            // get rid of all non-alphanumeric characters
            if (!($r >= 'a' && $r <= 'z') && !($r >= 'A' && $r <= 'Z') && !($r >= '1' && $r <= '9'))
                continue;
            $pass.=$r;

            $p++;
        endwhile;
        // if string is too short, remake it
        if (strlen($pass) < $min):
            $pass = $this->makePaystationSessionID($min, $max);
        endif;

        return $pass;
    }

    function directTransaction($url, $params) {
        
        $defined_vars = get_defined_vars();

        //use curl to get reponse	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch, CURLOPT_USERAGENT, $defined_vars['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }

    function initiate_paystation() {
              
        $returnURL = urlencode(  Mage::getUrl('paystation/standard/notificationurl'));
        $postbackURL = urlencode( Mage::getUrl('paystation/postback'));
        
        
        $_SESSION['paystation_success'] = false;
        $_SESSION['paystation_redirected'] = false;

        // Try to initiate a transaction with PayStation and get the redirect URL
        $checkoutfields = $this->getStandardCheckoutFormFields();
        $email = $checkoutfields['email'];
        
        $postback = Mage::getStoreConfig('payment/paystation_standard/postback', Mage::app()->getStore());
    
        $quoteID= Mage::getSingleton('checkout/session')->getQuoteId(); 
        if ($quoteID==NULL) $quoteID = "NULL";
        
        $quote = Mage::getModel('sales/quote')->load($quoteID);
        $reservedOrderId = $quote->getReservedOrderId();

        $authenticationKey  = Mage::getStoreConfig('payment/paystation_standard/hmac_key', Mage::app()->getStore());
        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();            

        
        $paystationURL = "https://www.paystation.co.nz/direct/paystation.dll";
        $amount = ($checkoutfields['final_amount'] * 100);
        $testMode = ($checkoutfields['testmode'] ? true : false);
        $postback =  Mage::getStoreConfig('payment/paystation_standard/postback', Mage::app()->getStore());
        $pstn_pi = $checkoutfields['merchant_id']; //"607113"; //Paystation ID
        $pstn_gi = $checkoutfields['gateway_id']; //"CARDPAY"; //Gateway ID
        $site = ''; // site can be used to differentiate transactions from different websites in admin.
        // $pstn_mr  = urlencode('schlocalhost-' . time()); // merchant reference is optional, but is a great way to tie a transaction in with a customer (this is displayed in Paystation Administration when looking at transaction details). Max length is 64 char. Make sure you use it!
        if ($testMode) $pstn_mr = urlencode($email.':test-mode:'.$reservedOrderId);
        else $pstn_mr = urlencode($email.':'.$reservedOrderId);//:'.$orderID);
        
        $merchantSession = urlencode(time() . '-' . $this->makePaystationSessionID(18, 18)); // max length of ms is 64 char 
        $_SESSION['paystation_ms'] = $merchantSession;
        $paystationParams = "paystation&pstn_pi=" . $pstn_pi . "&pstn_gi=" . $pstn_gi . "&pstn_ms=" . $merchantSession . "&pstn_am=" . $amount . "&pstn_mr=" . $pstn_mr . "&pstn_nr=t";
        $paystationParams .= "&pstn_du=".$returnURL;
        
        if ($postback=='1') $paystationParams .= "&pstn_dp=".$postbackURL;
        //echo "postback: ";
	//var_dump ($postback);
	//var_dump ($paystationParams); exit();

        //$this->setTransactionAdditionalInfo ('pstn_ms', $merchantSession);
        
        if ($testMode == true) {
            $paystationParams = $paystationParams . "&pstn_tm=t";
        }
        // if, possible pass the cc type so that the first step of selecting card type can be skipped on the external page

                
        $hmacBody = pack('a*', $pstn_HMACTimestamp).pack('a*', $hmacWebserviceName) . pack('a*', $paystationParams);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;
        $paystationURL.= $hmacGetParams;                        
 
        $initiationResult = $this->directTransaction($paystationURL, $paystationParams);
        preg_match_all("/<(.*?)>(.*?)\</", $initiationResult, $outarr, PREG_SET_ORDER);
        $n = 0;
        while (isset($outarr[$n])) {
            $retarr[$outarr[$n][1]] = strip_tags($outarr[$n][0]);
            $n++;
        }


        $_SESSION['redirectURL'] = '';
        if (isset($retarr['DigitalOrder']) && isset($retarr['PaystationTransactionID'])) {
            $_SESSION['redirectURL'] = $retarr['DigitalOrder'];

            
            return $_SESSION['redirectURL'];
        } else {
            Mage::throwException(Mage::helper('paystation')->__('Error: ' . $retarr['em']));
            return null;
        }
    }

}
