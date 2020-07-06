<?php

namespace Asiapay\Pdcptb\Controller\Adminhtml\Adminpdcptb;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception;
use Magento\Framework\Model\ResourceModel\Transaction;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use Magento\Sales\Model\Service\Order as ServiceOrder;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
   
class Update extends AbstractAdminpdcptb
{
	protected $viewLayoutFactory;

	
    public function __construct(Http $request,
        Context $context, 
        LayoutFactory $viewLayoutFactory
		)
    {
    	$this->request = $request;

        $this->viewLayoutFactory = $viewLayoutFactory;
		

        //parent::__construct( $context, $viewLayoutFactory);
		parent::__construct( $context,$viewLayoutFactory);
    }
	
	public function execute()
	{
		//retrieve order details
		$response = '';
    	$postUrl = '';
    	$postData = '';
    	$order_id = $this->request->getParam('order_id');
    	$objectManager = ObjectManager::getInstance();
    	$order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
		$increment_id = $order_object->getIncrementId()	;
		$store_id = $order_object->getData('store_id');
		$payment_method = $order_object->getPayment()->getMethodInstance();
		$error = '';
		//retrieve plugin parameter values
		$merchant_id = $payment_method->getConfigData('merchant_id',$store_id);
    	$api_url = $payment_method->getConfigData('api_url',$store_id);
    	$api_username = $payment_method->getConfigData('api_username',$store_id);
    	$api_password = $payment_method->getConfigData('api_password',$store_id);
    	$order_reference_no_prefix = $payment_method->getConfigData('order_reference_no_prefix',$store_id);

		//order prefix handler
		$order_ref = $increment_id;
		if($order_reference_no_prefix != '') $order_ref = $order_reference_no_prefix . '-' . $increment_id;
		//validate
		$error_msg = '';
		if($merchant_id == '')	$error_msg .= '- Merchant Id is not set. <br/>';
		if($api_url == '')		$error_msg .= '- API URL is not set. <br/>';
		if($api_username == '')	$error_msg .= '- API Username is not set. <br/>';
		if($api_password == '')	$error_msg .= '- API Password is not set. <br/>';
		
		if($error_msg != ''){
			//display module parameter errors
			echo '<b>MODULE SETUP ERROR:</b><br/>' . $error_msg ;
			echo '<br/>';
		}else{
			//call the query api
			$postUrl = $api_url;
			$postData = 'merchantId=' . $merchant_id . '&loginId=' . $api_username . '&password=' . $api_password . '&orderRef=' . $order_ref . '&actionType=Query';
			$response = $this->_httpPost($postUrl, $postData);
				
			if($response == ''){
				//display error
				echo 'QUERY ORDER REF: <b>' . $order_ref . '</b><br/>';
				echo 'QUERY URL: ' . $postUrl . '<br/>';
				echo 'QUERY DATA: ' . $postData . '<br/>';
				echo 'QUERY RESPONSE: No response recieved.<br/><br/>';
			}else{
				if(strpos($response,'resultCode') === 0){
					//display api error response
					parse_str($response,$responseArray);
					echo 'QUERY ORDER REF: <b>' . $order_ref . '</b><br/>';
					echo 'QUERY URL: ' . $postUrl . '<br/>';
					echo 'QUERY DATA: ' . $postData . '<br/>';
					echo 'QUERY RESPONSE: ' . $responseArray['errMsg'] . '<br/><br/>';
				}else{
					//display api response
					$xmlObj = simplexml_load_string($response);
					$recordsFound = count($xmlObj->children());
					$hasAtleastOneApproved = false;
					$payRef = '';
						
					if($recordsFound > 0){
						foreach($xmlObj->children() as $record) {
							if($record->orderStatus == 'Accepted' || $record->orderStatus == 'Authorized'){
								$hasAtleastOneApproved = true;
								if($payRef == ''){
									$payRef = $record->payRef;
								}
							}
						}
					}
					
					if($hasAtleastOneApproved){
						//$error;
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
							try{
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
											$objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')->send($invoice_object);
											
											$comment = "Invoice sent." ;
				
											$order_object->addStatusHistoryComment(
												__($comment, $invoice_object->getId()))
												->setIsCustomerNotified(true)
												->save();
							}else{
								//$this->_logLoggerInterface->debug(__('Cannot Invoice'));
							}
							}catch(Exception $e){
								$this->_logLoggerInterface->error($e);
							}
							
							
						}
						catch (Exception $e) {
							$error = $e;
							//print_r($e);
							$this->_logLoggerInterface->debug($error);
							$this->_logLoggerInterface->error($e);
						}
						
						if (!$error){
							echo 'Order State Has Been Updated To Processing. <br/><br/>';
						}
					}
		
				}
			}
		}
			
		echo '<a href="' . $this->getUrl('sales/order/view/', ['order_id'=>$order_object->getId()]) . '">[ Go Back To Order Page ]</a>';

    die();
    }
protected function _isAllowed() {
    return true;
    }
}
