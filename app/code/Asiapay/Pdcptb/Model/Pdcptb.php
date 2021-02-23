<?php

/**
* Pdcptb payment model
*
*/

namespace Asiapay\Pdcptb\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Helper\Data as HelperData;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
//use Magento\Sales\Model\Invoice\Payment as InvoicePayment;
//use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Event\Observer;
//use Magento\Store\Model\StoreManagerInterface;

class Pdcptb extends AbstractMethod
{
    protected $_modelOrder;
    protected $_viewLayoutFactory;
    protected $_logger;
    protected $_eventObserver;    
    protected $_storeManager;
    protected $_urlInterface;
    protected $orderRepository;

    public function __construct(Context $context, 
        Registry $registry, 
        ExtensionAttributesFactory $extensionFactory, 
        AttributeValueFactory $customAttributeFactory, 
        Observer $eventObserver,
        HelperData $paymentData, 
        ScopeConfigInterface $scopeConfig, 
        Logger $logger, 
        Order $modelOrder, 
        LayoutFactory $viewLayoutFactory, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        
        //StoreManagerInterface $storeManager,
        array $data = [])
    {
        $this->_modelOrder = $modelOrder;
        $this->_viewLayoutFactory = $viewLayoutFactory;
		$this->_logger = $logger;
		$this->_paymentData = $paymentData;
		$this->_eventObserver = $eventObserver;
		//$this->_storeManager=$storeManager;
		$this->_urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');

		$this->orderRepository = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Sales\Api\OrderRepositoryInterface');

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
        
    }

    const CGI_URL = 'https://www.paydollar.com/b2c2/eng/payment/payForm.jsp';
    const CGI_URL_TEST = 'https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp';
    const REQUEST_AMOUNT_EDITABLE = 'N';

    protected $_code  = 'pdcptb';
    protected $_formBlockType = 'Asiapay\Pdcptb\Block\PdcptbForm';
    protected $_allowCurrencyCode = ['HKD','USD','SGD','CNY','JPY','TWD','AUD','EUR','GBP','CAD','MOP','PHP','THB','MYR','IDR','KRW','SAR','NZD','AED','BND','VND'];
    
	public function getUrl()
    {
    	$url = $this->getConfigData('cgi_url');
    	//echo "no1".$url;
    	if(!$url)
    	{
    		$url = self::CGI_URL_TEST;
    	}
    	//echo "no2".$url;
    	return $url;
    }
    /**
     * Get session namespace
     *
     */
    public function getSession()
    {
        return ObjectManager::getInstance()->get('Asiapay\Pdcptb\Model\Pdcptb\Session');
    }

    /**
     * Get checkout session namespace
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
    }

    /**
     * Get current quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function retrieveLocale() {
		/** @var \Magento\Framework\ObjectManagerInterface $om */
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		/** @var \Magento\Framework\Locale\Resolver $resolver */
		$resolver = $om->get('Magento\Framework\Locale\Resolver');
		$templang = $resolver->getLocale();
		
		//output to text file to check language code
			// $file = fopen("test.txt","w");
			// echo fwrite($file, $templang);
			// fclose($file);
		//Returns language code for PayDollar/PesoPay/SiamPay
		//Chinese - Simplified
		if($templang == 'zh_Hans_CN'){
			return 'X';
		}
		//Japanese
		else if($templang == 'ja_JP'){
			return 'J';
		}
		//Korean
		else if($templang == 'ko_KR'){
			return 'K';
		}
		//Chinese - Traditional
		else if($templang == 'zh_Hant_HK' || $templang == 'zh_Hant_TW' || $templang == 'zh_TW' ){
			return 'C';
		}
		//Thai
		else if($templang == 'th_TH'){
			return 'T';
		}
		//German
		else if($templang == 'de_DE'){
			return 'G';
		}
		//French
		else if($templang == 'fr_FR'){
			return 'F';
		}
		//Russian
		else if($templang == 'ru_RU'){
			return 'R';
		}
		//Vietnamese
		else if($templang == 'vi_VN'){
			return 'V';
		}	
		//Spanish
		else if($templang == 'es_ES'){
			return 'S';
		}
		else
		//English for other countries
			return 'E';
	}
    
    public function getCheckoutFormFields()
	{
		//for Magento v1.3.x series
		/*
		$a = $this->getQuote()->getShippingAddress();
		$b = $this->getQuote()->getBillingAddress();
		$currency_code = $this->getQuote()->getBaseCurrencyCode();
		$cost = $a->getBaseSubtotal() - $a->getBaseDiscountAmount();
		$shipping = $a->getBaseShippingAmount();
		
		$_shippingTax = $this->getQuote()->getShippingAddress()->getBaseTaxAmount();
		$_billingTax = $this->getQuote()->getBillingAddress()->getBaseTaxAmount();
		$tax = sprintf('%.2f', $_shippingTax + $_billingTax);
		$cost = sprintf('%.2f', $cost + $tax);
		*/
		
		//for Magento v1.5.x
		/*$order = $this->getOrder();*/
		
		//for Magento v1.6.x series
		$order = $this->_modelOrder;
		$order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        
        $currency_code = $order->getBaseCurrencyCode();
		$cur = $this->getIsoCurrCode($currency_code);
		
		$grandTotalAmount = sprintf('%.2f', $order->getBaseGrandTotal());  
		
		$gatewayLanguage = substr($this->getConfigData('gateway_language'), 0, 1);
		
		$gatewayLanguage = preg_replace('/\s+/', '', $gatewayLanguage);
		
		if(empty($gatewayLanguage)){
			$lang = $this->retrieveLocale();
		}
		else{
			if (preg_match("/^[TGFRVSCEXKJcexkjtgfrvs]/", $gatewayLanguage, $matches)){
				$lang = strtoupper($matches[0]);
			}else{
				$lang = 'C';
			}
		}

		$orderReferencePrefix = trim($this->getConfigData('order_reference_no_prefix'));
		
		if (is_null($orderReferencePrefix) || $orderReferencePrefix == ''){
			$orderReferenceValue = $this->getCheckout()->getLastRealOrderId();
		}else{
			$orderReferenceValue = $this->getConfigData('order_reference_no_prefix') . "-" . $this->getCheckout()->getLastRealOrderId();
		}
		
		
		//for Magento v2.x series
		$merchantId = $this->getConfigData('merchant_id');
		$paymentType = $this->getConfigData('pay_type');
		$secureHashSecret = $this->getConfigData('secure_hash_secret');

		//$order = $this->_eventObserver->getOrder();
		//$order_id = $order->getIncreamentId();
		//$this->_logger->info($order_id);
		//$product_id = $this->_eventObserver->getProduct()->getId();
		//echo $product_id;
		//$this->getUrl();
		//echo "this is the merchant id " . $merchantId;

		/* memberpay start */
		$memberpay_service = $this->getConfigData('memberpay');
		$memberpay_memberid = '';
		$memberPay_email = '';
		$customer_acctAgeInd = "01";
		$customer_acctAuthMethod = "01"; // as guest
		$customer_acctAuthDate = "";

		//3ds2.0 shipping for both guest and login customer


		$shipData = $this->getCustomerShippingData();
		$customer_ship_phonenum = $customer_bill_phonenum = $shipData['telephone'];
		$customer_bill_phonenum = preg_replace('/\D/', '', $customer_bill_phonenum);
		$customer_ship_email = $shipData['email'];
		$customer_ship_countryID = $customer_bill_countryID = $shipData['country_id'];
		$customer_ship_countryCode = $customer_bill_countryCode = $customer_bill_phonecountryCode = ObjectManager::getInstance()->get('Asiapay\Pdcptb\Helper\Data')->getCountryCodeNumeric($customer_ship_countryID);
		$customer_ship_street = (is_array($shipData['street']))? $shipData['street']:explode("\n", $shipData['street']);
		$customer_ship_street0 = $customer_bill_street0 = trim((array_key_exists(0, $customer_ship_street))?$customer_ship_street[0]:'');	
		$customer_ship_street1 = $customer_bill_street1 = trim((array_key_exists(1, $customer_ship_street))?$customer_ship_street[1]:'');	
		$customer_ship_street2 = $customer_bill_street2 = trim((array_key_exists(2, $customer_ship_street))?$customer_ship_street[2]:'');
		$customer_city = $customer_bill_city = $shipData['city'];
		$customer_postcode = $shipData['postcode'];

		if (/*Mage::app()->isInstalled() && */ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->isLoggedIn()) {            
			$memberpay_memberid = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getEmail();
			$memberPay_email = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getEmail();

			//billing address

			$customer_bill_phonenum = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryBillingAddress()->getTelephone();
			$customer_bill_phonenum = preg_replace('/\D/', '', $customer_bill_phonenum);
			$customer_bill_countryID = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryBillingAddress()->getCountryId();
			$customer_bill_phonecountryCode = ObjectManager::getInstance()->get('Asiapay\Pdcptb\Helper\Data')->getphonecode($customer_bill_countryID);
			$customer_bill_countryCode = ObjectManager::getInstance()->get('Asiapay\Pdcptb\Helper\Data')->getCountryCodeNumeric($customer_bill_countryID);
			$customer_bill_street = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryBillingAddress()->getStreet();

			$customer_bill_street0 = (array_key_exists(0, $customer_bill_street))?$customer_bill_street[0]:'';	
			$customer_bill_street1 = (array_key_exists(1, $customer_bill_street))?$customer_bill_street[1]:'';	
			$customer_bill_street2 = (array_key_exists(2, $customer_bill_street))?$customer_bill_street[2]:'';	
			$customer_bill_city = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryBillingAddress()->getCity();
			$customer_postcode = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryBillingAddress()->getPostcode();

			//account info related
			$customer_acct_createdate = date('Ymd' ,ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getCreatedAtTimestamp());
		
			$customer_daydiff = $this->getDateDiff($customer_acct_createdate);

			$customer_acct_ageind =$this->getAcctAgeInd($customer_daydiff);

			$diffAdd = $this->getDiffBillShipAddress();

			if($diffAdd == "T")
				$shippingDetl = "01"; // Ship to cardholder’s billing address
			else
				$shippingDetl = "03"; // Ship to address that is different than the cardholder’s billing address

			$countOrder = $this->getCustomerAllOrdersComplete();
			$countOrderAnyDay = $this->getCustomerAllOrdersAllStatus("day");
			$countOrderAnyYear = $this->getCustomerAllOrdersAllStatus("year");
			$customer_acctAuthMethod = "02"; // Login to the cardholder account at the merchant system using merchant‘s own credentials

			$authdate = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getData('updated_at');
			// $customer_acctAuthDate = gmdate("Ymd H:i:s", time());

			$customer_acctAuthDate = gmdate("Ymd" , strtotime($authdate));
			// $this->getCustomerData();
        }else{
        	$diffAdd = "T";
        	$shippingDetl = "01";// Ship to cardholder’s billing address
        	$customer_acct_createdate = $customer_acct_ageind = $countOrder = $countOrderAnyDay = $countOrderAnyYear = "";
        }


        

        $txnType = $this->getConfigData('three_ds_transtype');

        $threedschallengepref = $this->getConfigData('three_ds_challenge_preference');
        //echo $memberPay_email;
		/* memberpay end */

		/*echo "URL BASE = ".$this->_storeManager->getStore()
           ->getUrl('pdcptb/pdcptb/success');*/
           
		$fields = [
			'merchantId'				=> $merchantId,
			//for Magento v1.3.x series
			//'amount'					=> sprintf('%.2f', $cost + $shipping),
			'amount'					=> $grandTotalAmount, 
			'currCode'					=> $cur,
			'orderRef'					=> $orderReferenceValue,
			'successUrl'				=> $this->_urlInterface->getUrl('pdcptb/pdcptb/success'),
			'cancelUrl'					=> $this->_urlInterface->getUrl('pdcptb/pdcptb/cancel'),
			'failUrl'					=> $this->_urlInterface->getUrl('pdcptb/pdcptb/failure'),
			'lang'						=> $lang,
			'payMethod'					=> 'ALL',
			'payType'					=> $paymentType,
			'secureHash'				=> $this->generatePaymentSecureHash($merchantId, $orderReferenceValue, $cur, $grandTotalAmount, $paymentType, $secureHashSecret),
			'memberPay_service'			=> $memberpay_service,
			'memberPay_memberId'		=> $memberpay_memberid,
			'memberPay_email'			=> $memberPay_email,
			'failRetry'					=> 'no',


			//for 3ds2.0
			//Basic Parameters Customer Info
			'threeDSTransType'				=> isset($txnType)&&($txnType)?$txnType:"",
			'threeDSCustomerEmail'			=> isset($memberPay_email)&&($memberPay_email)?$memberPay_email:"",
			'threeDSMobilePhoneCountryCode' => isset($customer_bill_phonecountryCode)&&($customer_bill_phonecountryCode)?$customer_bill_phonecountryCode:"",
			'threeDSMobilePhoneNumber' 		=> isset($customer_bill_phonenum)&&($customer_bill_phonenum)?$customer_bill_phonenum:"",
			'threeDSHomePhoneCountryCode'	=> isset($customer_bill_phonecountryCode)&&($customer_bill_phonecountryCode)?$customer_bill_phonecountryCode:"",
			'threeDSHomePhoneNumber'		=> isset($customer_bill_phonenum)&&($customer_bill_phonenum)?$customer_bill_phonenum:"",
			'threeDSWorkPhoneCountryCode' 	=> isset($customer_bill_phonecountryCode)&&($customer_bill_phonecountryCode)?$customer_bill_phonecountryCode:"",
			'threeDSWorkPhoneNumber'		=> isset($customer_bill_phonenum)&&($customer_bill_phonenum)?$customer_bill_phonenum:"",
			'threeDSIsFirstTimeItemOrder'	=> '',
			'threeDSChallengePreference'	=> isset($threedschallengepref)&&($threedschallengepref)?$threedschallengepref:"",

			//recurring payment related
			'threeDSRecurringFrequency'		=>'',
			'threeDSRecurringExpiry'		=>'',

			//Billing address related
			'threeDSBillingCountryCode'		=> isset($customer_bill_countryCode)&&($customer_bill_countryCode)?$customer_bill_countryCode:"",
			'threeDSBillingState'			=> isset($customer_bill_countryID)&&($customer_bill_countryID)?$customer_bill_countryID:"",
			'threeDSBillingCity' 			=> isset($customer_bill_city)&&($customer_bill_city)?$customer_bill_city:"",
			'threeDSBillingLine1' 			=> isset($customer_bill_street0)&&($customer_bill_street0)?$customer_bill_street0:"",
			'threeDSBillingLine2'			=> isset($customer_bill_street1)&&($customer_bill_street1)?$customer_bill_street1:"",
			'threeDSBillingLine3'			=> isset($customer_bill_street2)&&($customer_bill_street2)?$customer_bill_street2:"",
			'threeDSBillingPostalCode' 		=> isset($customer_postcode)&&($customer_postcode)?$customer_postcode:"",

			//Shipping / Delivery Related
			'threeDSDeliveryTime'			=> '',
			'threeDSDeliveryEmail'			=> isset($customer_ship_email)&&($customer_ship_email)?$customer_ship_email:"",
			'threeDSShippingDetails' 		=> isset($shippingDetl)&&($shippingDetl)?$shippingDetl:"",
			'threeDSShippingCountryCode' 	=> isset($customer_ship_countryCode)&&($customer_ship_countryCode)?$customer_ship_countryCode:"",
			'threeDSShippingCity'			=> isset($customer_city)&&($customer_city)?$customer_city:"",
			'threeDSShippingLine1'			=> isset($customer_ship_street0)&&($customer_ship_street0)?$customer_ship_street0:"",
			'threeDSShippingLine2' 			=> isset($customer_ship_street1)&&($customer_ship_street1)?$customer_ship_street1:"",
			'threeDSShippingLine3'			=> isset($customer_ship_street2)&&($customer_ship_street2)?$customer_ship_street2:"",
			'threeDSShippingPostalCode'		=> isset($customer_postcode)&&($customer_postcode)?$customer_postcode:"",
			'threeDSIsAddrMatch'			=> isset($diffAdd)&&($diffAdd)?$diffAdd:"",


			//Gift Card / Prepaid Card Purchase Related
			'threeDSGiftCardAmount'			=> '',
			'threeDSGiftCardCurr'			=> '',
			'threeDSGiftCardCount'			=> '',


			//Pre-Order Purchase Related
			'threeDSPreOrderReason'			=> '',
			'threeDSPreOrderReadyDate'		=> '',

			//Account Info Related
			'threeDSAcctCreateDate'					=> isset($customer_acct_createdate)&&($customer_acct_createdate)?$customer_acct_createdate:"",
			'threeDSAcctAgeInd'						=> isset($customer_acct_ageind)&&($customer_acct_ageind)?$customer_acct_ageind:"",
			'threeDSAcctLastChangeDate' 			=> '',
			'threeDSAcctLastChangeInd' 				=> '',
			'threeDSAcctPwChangeDate'				=> '',
			'threeDSAcctPwChangeInd'				=> '',
			'threeDSAcctPurchaseCount' 				=> isset($countOrder)&&($countOrder)?$countOrder:"",
			'threeDSAcctCardProvisionAttempt'		=> '',
			'threeDSAcctNumTransDay'				=> isset($countOrderAnyDay)&&($countOrderAnyDay)?$countOrderAnyDay:"",
			'threeDSAcctNumTransYear'				=> isset($countOrderAnyYear)&&($countOrderAnyYear)?$countOrderAnyYear:"",
			'threeDSAcctPaymentAcctDate' 			=> '',
			'threeDSAcctPaymentAcctInd' 			=> '',
			'threeDSAcctShippingAddrLastChangeDate'	=> '',
			'threeDSAcctShippingAddrLastChangeInd'	=> '',
			'threeDSAcctIsShippingAcctNameSame'		=> '',
			'threeDSAcctIsSuspiciousAcct'			=> '',

			//Account Authentication Info Related
			'threeDSAcctAuthMethod'			=> isset($customer_acctAuthMethod)&&($customer_acctAuthMethod)?$customer_acctAuthMethod:"",
			'threeDSAcctAuthTimestamp'		=> isset($customer_acctAuthDate)&&($customer_acctAuthDate)?$customer_acctAuthDate:"",

			//Pay Token Related 
			'threeDSPayTokenInd'			=> '',
				
		];


		// echo "<pre>";
		
		// print_r($fields);
		
		// Run through fields and replace any occurrences of & with the word 
		// 'and', as having an ampersand present will conflict with the HTTP
		// request.
		$filtered_fields = [];
        foreach ($fields as $k=>$v) {
            $value = str_replace("&","and",$v);
            $filtered_fields[$k] =  $value;
        }
        // echo $this->getUrl()."?".http_build_query($filtered_fields);
        // exit;
        return $filtered_fields;
	}

	public function getProduct()
    {

        $product_id = $this->_eventObserver->getProduct()->getId();
        $this->logger->info($product_id);
        /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($product_id);
        $this->logger->info('Product Info', $product->getData());*/

    }
    public function getIsoCurrCode($magento_currency_code) {
		switch($magento_currency_code){
		case 'HKD':
			$cur = '344';
			break;
		case 'USD':
			$cur = '840';
			break;
		case 'SGD':
			$cur = '702';
			break;
		case 'CNY':
			$cur = '156';
			break;
		case 'JPY':
			$cur = '392';
			break;		
		case 'TWD':
			$cur = '901';
			break;
		case 'AUD':
			$cur = '036';
			break;
		case 'EUR':
			$cur = '978';
			break;
		case 'GBP':
			$cur = '826';
			break;
		case 'CAD':
			$cur = '124';
			break;
		case 'MOP':
			$cur = '446';
			break;
		case 'PHP':
			$cur = '608';
			break;
		case 'THB':
			$cur = '764';
			break;
		case 'MYR':
			$cur = '458';
			break;
		case 'IDR':
			$cur = '360';
			break;
		case 'KRW':
			$cur = '410';
			break;
		case 'SAR':
			$cur = '682';
			break;
		case 'NZD':
			$cur = '554';
			break;
		case 'AED':
			$cur = '784';
			break;
		case 'BND':
			$cur = '096';
			break;
		case 'VND':
			$cur = '704';
			break;
		case 'INR':
			$cur = '356';
			break;
		default:
			$cur = '344';
		}		
		return $cur;
	}
	
	public function generatePaymentSecureHash($merchantId, $merchantReferenceNumber, $currencyCode, $amount, $paymentType, $secureHashSecret) {

		$buffer = $merchantId . '|' . $merchantReferenceNumber . '|' . $currencyCode . '|' . $amount . '|' . $paymentType . '|' . $secureHashSecret;
		//echo $buffer;
		return sha1($buffer);

	}
	
    public function createFormBlock($name)
    {
        $block = $this->_viewLayoutFactory->create()->createBlock('pdcptb/pdcptb_form', $name)
            ->setMethod('pdcptb')
            ->setPayment($this->getPayment())
            ->setTemplate('Asiapay_Pdcptb::asiapay/pdcptb/form.phtml');

        return $block;
    }
	
    
    public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if($currency_code == ""){
        }else{
	        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
	            throw new \Exception(__('Selected currency code ('.$currency_code.') is not compatabile with PayDollar'));
	        }
        }
        return $this;
    }
	
    public function onOrderValidate(Payment $payment)
    {
       return $this;
    }

    /*public function onInvoiceCreate(InvoiceService $payment)
    {
		return $this;
    }*/
	
    public function getOrderPlaceRedirectUrl()
    {
          return Pdcptb::getUrl('pdcptb/pdcptb/sss');
    }

    public function getDateDiff($d){
    		$datenow = date('Ymd');
			$dt1 = new \DateTime($datenow);
			$dt2 = new \DateTime($d);
			$interval = $dt1->diff($dt2)->format('%a');
			return $interval;
    }

    public function getAcctAgeInd($d){
    	switch ($d) {
    		case 0:
    			# code...
    			$ret = "02";
    			break;
    		case $d<30:
    			# code...
    			$ret = "03";
    			break;
    		case $d>30 && $d<60:
    			# code...
    			$ret = "04";
    			break;
    		case $d>60:
    			$ret = "05"	;
				break;	
    		default:
    			# code...
    			break;
    	}
    	return $ret;

    }

    public function getDiffBillShipAddress(){
    		$txtRet = "F";

    		$shipData = $this->getCustomerShippingData();

			$customer_ship_countryCode = ObjectManager::getInstance()->get('Asiapay\Pdcptb\Helper\Data')->getCountryCodeNumeric($shipData['country_id']);

			$customer_ship_street = (is_array($shipData['street']))? $shipData['street']:explode("\n", $shipData['street']);

			$customer_ship_street0 = (array_key_exists(0, $customer_ship_street))?$customer_ship_street[0]:'';	

			$customer_ship_street1 = (array_key_exists(1, $customer_ship_street))?$customer_ship_street[1]:'';	
						
			$customer_ship_street2 = (array_key_exists(2, $customer_ship_street))?$customer_ship_street[2]:'';


    		$b1 = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getDefaultBillingAddress()->getStreet();

    		$b2 = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getDefaultShippingAddress()->getCity();

			$b3 = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getDefaultShippingAddress()->getPostcode();

    		$s1 = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getDefaultShippingAddress()->getStreet();

    		$s2 = $shipData['city'];

			$s3 = $shipData['postcode'];

			if($b1 == $s1 && $b2 == $s2 && $b3 == $s3){
				$txtRet = "T";
			}

			return $txtRet;

    }


    public function getCustomerAllOrdersComplete(){
    	$customerId = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getId();
    	// echo $customerId;
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$lastyear = date('Y-m-d', strtotime("-6 months"));
		$orderCollection = $objectManager->create('\Magento\Sales\Model\ResourceModel\Order\Collection');
		$orderCollection->addAttributeToFilter('customer_id',$customerId)
			        ->addAttributeToFilter('status','complete')
			        ->addAttributeToFilter('created_at', array('gteq'  => $lastyear))->load();
    
		return count($orderCollection->getData());

    }

    public function getCustomerAllOrdersAllStatus($dy){
    	$customerId = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getId();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

    	$lastdayyear = date('Y-m-d', strtotime("-1 $dy"));

		$orderCollection = $objectManager->create('\Magento\Sales\Model\ResourceModel\Order\Collection');
		$orderCollection->addAttributeToFilter('customer_id',$customerId)
			        ->addAttributeToFilter('created_at', array('gteq'  => $lastdayyear))->load();

		return count($orderCollection->getData());
    }


    public function getCustomerShippingData(){
    	$shipping_data = array();
    	$order = $this->_modelOrder;
		$order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
		$order_information = $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
		$a = $order_information->getShippingAddress();
		// print_r($a);
		// echo count($a);
		if($a){
			// echo 1;
			$shipping_data = $a->getData();
		}else{
			// echo 123;
			$shipping_data['telephone'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryShippingAddress()->getTelephone();

			$shipping_data['country_id'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryShippingAddress()->getCountryId();
			$customer_ship_countryCode = ObjectManager::getInstance()->get('Asiapay\Pdcptb\Helper\Data')->getCountryCodeNumeric($shipping_data['country_id']);

			$shipping_data['street'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getDefaultShippingAddress()->getStreet();
			
			$arrSt = $shipping_data['street'];

			// print_r($arrSt);

			$customer_ship_street0 = (array_key_exists(0, $arrSt))?$arrSt[0]:'';	

			$customer_ship_street1 = (array_key_exists(1, $arrSt))?$arrSt[1]:'';	
						
			$customer_ship_street2 = (array_key_exists(2, $arrSt))?$arrSt[2]:'';

			$shipping_data['city'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryShippingAddress()->getCity();

			$shipping_data['postcode'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getPrimaryShippingAddress()->getPostcode();

			$shipping_data['email'] = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getEmail();
		}
		// if()
		// $shipping_data = $order_information->getShippingAddress()->getData();
		// echo "<pre>";
		// 		print_r($shipping_data);
		return $shipping_data;
    }

    public function getCustomerData(){
    	$customerData = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getData();
    	echo "<pre>";
    	print_r($customerData);
    	// print_r($customer->getData('updated_at'));
		// $logCustomer = ObjectManager::getInstance()->get('Magento\Customer\Model\log')->loadByCustomer($customer);
		// $lastVisited = $logCustomer->getLoginAtTimestamp();
		// $lastVisited = date('Y-m-d H:i:s', $lastVisited);
		// echo $lastVisited;
		// return $customerData->getData('updated_at');
    }


}