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

	protected $request;
	
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
		if($this->request->getParam('secureHash')!=null){
			$secureHash = $this->request->getParam('secureHash');
		}else{
			$secureHash = "";
		}
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		
		$order_object = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderNumber);

		$logger = $objectManager->create('\Psr\Log\LoggerInterface');
		
		$secureHashSecret = "";
		if(!empty($orderNumber)){
			$payment_method = $order_object->getPayment()->getMethodInstance();
			$secureHashSecret = $payment_method->getConfigData('secure_hash_secret');
		}else{
			exit;
		}
		
		$dbCurrency = $order_object->getBaseCurrencyCode();
		/* convert currency type into numerical ISO code start*/

		$dbCurrencyIso = $this->_getIsoCurrCode($dbCurrency);
		/* convert currency type into numerical ISO code end*/
			
		//get grand total amount from Magento's sales order data for this order id (for comparison with the gateway's POSTed amount)
		$dbAmount = sprintf('%.2f', $order_object->getBaseGrandTotal());
		
		if(trim($secureHashSecret) != ""){	
			$secureHashs = explode ( ',', $secureHash );
			// while ( list ( $key, $value ) = each ( $secureHashs ) ) {
			foreach($secureHashs as $key => $value) {
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
						$order_object->setState(\Magento\Sales\Model\Order::STATE_PROCESSING); 
						$order_object->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING); 
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
				catch (\Exception $e){
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
				$order_object->cancel()->save();
				$order_object->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, $comment, true)->save();
				echo "Order Status (cancelled) update successful ";
				echo "Transaction Rejected / Failed.";
			}	
		}
	}
	
	protected function _isAllowed() {
        return true;
    }

}

?>
