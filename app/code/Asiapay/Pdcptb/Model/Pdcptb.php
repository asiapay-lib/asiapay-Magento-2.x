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
		$this->_eventObserver = $eventObserver;
		//$this->_storeManager=$storeManager;
		$this->_urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
    }

    const CGI_URL = 'https://www.paydollar.com/b2c2/eng/payment/payForm.jsp';
    const CGI_URL_TEST = 'https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp';
    const REQUEST_AMOUNT_EDITABLE = 'N';

    protected $_code  = 'pdcptb';
    protected $_formBlockType = 'Asiapay\Pdcptb\Block\PdcptbForm';
    protected $_allowCurrencyCode = ['HKD','USD','SGD','CNY','JPY','TWD','AUD','EUR','GBP','CAD','MOP','PHP','THB','MYR','IDR','KRW','SAR','NZD','AED','BND'];
    
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
		
		if (preg_match("/^[CEXKJcexkj]/", $gatewayLanguage, $matches)){
			$lang = strtoupper($matches[0]);
		}else{
			$lang = 'C';
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
		if (/*Mage::app()->isInstalled() && */ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->isLoggedIn()) {            
			$memberpay_memberid = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getEmail();
			$memberPay_email = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer()->getEmail();
        }
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
			'failRetry'					=> 'no'
				
		];

		// Run through fields and replace any occurrences of & with the word 
		// 'and', as having an ampersand present will conflict with the HTTP
		// request.
		$filtered_fields = [];
        foreach ($fields as $k=>$v) {
            $value = str_replace("&","and",$v);
            $filtered_fields[$k] =  $value;
        }
        
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
}