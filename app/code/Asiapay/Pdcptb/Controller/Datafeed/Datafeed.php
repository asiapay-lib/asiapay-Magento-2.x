<?php

/*
 * 
 * This datafeed page for the Magento AsiaPay payment module updates the order status to 'processing', 
 * 
 * sends an email, and creates an invoice automatically if the online transaction is successful.
 * 
 */
 
namespace Asiapay\Pdcptb\Controller\Datafeed;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order;

class Datafeed extends AbstractDatafeed
{
	protected $viewLayoutFactory;
	
	protected $_invoiceService;
	
	protected $_transaction;
	
	public function __construct(Http $request,
        Context $context, 
        LayoutFactory $viewLayoutFactory
		)
    {
    	$this->request = $request;

        $this->viewLayoutFactory = $viewLayoutFactory;
		parent::__construct($context,$viewLayoutFactory);
    }
	
	
	
	public function execute()
	{

		//Receive POSTed variables from the gateway
		/*$src = $_POST['src'];
		$prc = $_POST['prc'];
		$ord = $_POST['Ord'];
		$holder = $_POST['Holder'];
		$successCode = $_POST['successcode'];
		$ref = $_POST['Ref'];
		$payRef = $_POST['PayRef'];
		$amt = $_POST['Amt'];
		$cur = $_POST['Cur'];
		$remark = $_POST['remark'];
		$authId = $_POST['AuthId'];
		$eci = $_POST['eci'];
		$payerAuth = $_POST['payerAuth'];
		$sourceIp = $_POST['sourceIp'];
		$ipCountry = $_POST['ipCountry'];*/
		
		$src = $this->request->getParam('src');
		$prc = $this->request->getParam('prc');
		$ord = $this->request->getParam('Ord');
		$holder = $this->request->getParam('Holder');
		$successCode = $this->request->getParam('successcode');
		$ref = $this->request->getParam('Ref');
		$payRef = $this->request->getParam('PayRef');
		$amt = $this->request->getParam('Amt');
		$cur = $this->request->getParam('Cur');
		$remark = $this->request->getParam('remark');
		$authId = $this->request->getParam('AuthId');
		$eci = $this->request->getParam('eci');
		$payerAuth = $this->request->getParam('payerAuth');
		$sourceIp = $this->request->getParam('sourceIp');
		$ipCountry = $this->request->getParam('ipCountry');
		//explode reference number and get the value only
		$flag = preg_match("/-/", $ref);
		
		echo "OK! " . "Order Ref. No.: ". $ref . " | ";
			
		if ($flag == 1){
			$orderId = explode("-",$ref);
			$orderNumber = $orderId[1];
		}else{
			$orderNumber = $ref;
		}

		date_default_timezone_set('Asia/Hong_Kong');
		$phperrorPath = 'log'.$ord.'.txt';
		/*error_log('['.date("F j, Y, g:i a e O").']'."src = ".$src." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."prc = ".$prc." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."ord = ".$ord." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."holder = ".$holder." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."successCode = ".$successCode." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."ref = ".$ref." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."payRef = ".$payRef." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."amt = ".$amt." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."cur = ".$cur." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."remark = ".$remark." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."authId = ".$authId." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."eci = ".$eci." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."payerAuth = ".$payerAuth." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."sourceIp = ".$sourceIp." \n", 3,  $phperrorPath);
		error_log('['.date("F j, Y, g:i a e O").']'."ipCountry = ".$ipCountry." \n", 3,  $phperrorPath);
		*/
		//if(isset($_POST['secureHash'])){
		if($this->request->getParam('secureHash')!=null){
			$secureHash = $this->request->getParam('secureHash');
		}else{
			$secureHash = "";
		}
		//error_log('['.date("F j, Y, g:i a e O").']'."secureHash = ".$secureHash." \n", 3,  $phperrorPath);
		//confirmation sent to the gateway to explain that the variables have been sent
		//echo "OK! " . "Order Ref. No.: ". $ref . " | ";
		/*
		//Instantiate Mage_Sales_Model_Order class and load the order ID
		//Note: increment ID is the system generated number per order by Magento 
		*/
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		//$orderId = $objectManager->create('\Magento\Checkout\Model\Session')->getLastOrderId();
		/*$order_object = "";
		if($orderId == $payRef){
			$order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
		}else{
			//error.log(something);
		}   */ 

		//$order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($ref);
		$order_object = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderNumber);

		//$order_object = Mage::getModel('sales/order')->load($payRef, 'increment_id');
		 
		$logger = $objectManager->create('\Psr\Log\LoggerInterface');
		//$store_id = $order_object->getData('store_id');
		//$store_id = $order_object->getStoreId();
		//echo "orderId:".$orderId;
		$payment_method = $order_object->getPayment()->getMethodInstance();
		$dbCurrency = $order_object->getBaseCurrencyCode();
		/* convert currency type into numerical ISO code start*/

		$dbCurrencyIso = $this->_getIsoCurrCode($dbCurrency);
		/* convert currency type into numerical ISO code end*/
			
		//get grand total amount from Magento's sales order data for this order id (for comparison with the gateway's POSTed amount)
		//$dbAmount = $order_object->getData('base_grand_total');
		$dbAmount = sprintf('%.2f', $order_object->getBaseGrandTotal());



		//$secureHashSecret = $payment_method->getConfigData('secure_hash_secret',$store_id);
		$secureHashSecret = $payment_method->getConfigData('secure_hash_secret');
		if(trim($secureHashSecret) != ""){	
			$secureHashs = explode ( ',', $secureHash );
			while ( list ( $key, $value ) = each ( $secureHashs ) ) {
				$verifyResult = $this->_verifyPaymentDatafeed($src, $prc, $successCode, $ref, $payRef, $cur, $amt, $payerAuth, $secureHashSecret, $value);
				if ($verifyResult) {
					break ;
				}
			}	
			if (! $verifyResult) {
				exit("Secure Hash Validation Failed");
			}
		}
		/* secureHash validation end*/ 
		if ($successCode == 0 && $prc == 0 && $src == 0){
			if ($dbAmount == $amt && $dbCurrencyIso == $cur){	
				$error = "";
				try {	
					//update order status to processing
						$comment = "Payment was Accepted. Payment Ref: " . $payRef ;
						$order_object->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)->save(); 
						$order_object->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PROCESSING, $comment, true)->save();
						$orderCommentSender = $objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
						$orderCommentSender->send($order_object, $notify='1' , $comment);// $comment yout comment
						//add payment record for the order
							
						$payment = $order_object->getPayment()
												->setMethod('pdcptb')
												->setTransactionId($payRef)
												->setIsTransactionClosed(true);
						$order_object->setPayment($payment);
						$payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT);
						$order_object->save();
						//create invoice
						//Instantiate ServiceOrder class and prepare the invoice 
						if($order_object->canInvoice()){
							$invoice_object = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order_object);
							
							// Make sure there is a qty on the invoice			
							if (!$invoice_object->getTotalQty()) {
								throw new \Magento\Framework\Exception\LocalizedException(
								__('You can\'t create an invoice without products.'));
							}
							// Register as invoice item
							$invoice_object->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
							$invoice_object->register();
									
							// Save the invoice to the order
							$transaction = $objectManager->create('Magento\Framework\DB\Transaction')
												->addObject($invoice_object)
												->addObject($invoice_object->getOrder());

							$transaction->save();
							// Magento\Sales\Model\Order\Email\Sender\InvoiceSender
							//$this->
							$objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')->send($invoice_object);
							$comment = "Invoice sent." ;
							$order_object->addStatusHistoryComment(
												__($comment, $invoice_object->getId()))
												->setIsCustomerNotified(true)
												->save();
						}
					
				}
				/*catch (Mage_Core_Exception $e) {
					$error = $e;
					//print_r($e);
					Mage::log($error);
					Mage::logException($e);
				}*/
				catch (Exception $e){
					echo $e->getMessage();
				}
				
				if (!$error){
					echo "Order status (processing) update successful";
				}
				}else{
					if (($dbAmount != $amt)){  
						echo "Amount value: DB " . (($dbAmount == '') ? 'NULL' : $dbAmount) . " is not equal to POSTed " . $amt . " | ";
						echo "Possible tamper - Update failed";
					}else if (($dbCurrencyIso != $cur)){
						echo "Currency value: DB " . (($dbCurrency == '') ? 'NULL' : $dbCurrency) . " (".$dbCurrencyIso.") is not equal to POSTed " . $cur . " | ";
						echo "Possible tamper - Update failed";
					}else{
						echo "Other unknown error - Update failed";
					}
				}
			
		}else{
			/* WRAPPED WITH IF/ELSE STATEMENT TO PREVENT CHANGING OF STATUS TO CANCELED WHEN ALREADY GOT ACCEPTED TRANSACTION */
			$dbState = $order_object->getData('state');
			if($dbState == \Magento\Sales\Model\Order::STATE_PROCESSING || $dbState == \Magento\Sales\Model\Order::STATE_COMPLETE){
				//do nothing here
				echo "The order state is already set to  \"".dbState."\", so we cannot set it to \"".\Magento\Sales\Model\Order::STATE_CANCELED."\" anymore";
			}else{
				//update order status to canceled
				$comment = "Payment was Rejected. Payment Ref: " . $payRef ;	
				//$order_object->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $comment, 0)->save();	
				$order_object->cancel()->save();
				//$order_object->sendOrderUpdateEmail(true, $comment);	//for sending order email update to customer
				$order_object->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, $comment, true)->save();
				echo "Order Status (cancelled) update successful";
				echo "Transaction Rejected / Failed.";
			}	
		}
	}
	
	protected function _isAllowed() {
        return true;
    }

}

?>
