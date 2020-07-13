<?php

namespace Asiapay\Pdcptb\Controller\Adminhtml\Adminpdcptb;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ObjectManager;

class Cancel extends AbstractAdminpdcptb
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
		//parent::__construct( $context);
		parent::__construct( $context,$viewLayoutFactory);
    }

	public function execute()
	{
		//retrieve order details
        $order_id = $this->request->getParam('order_id');
        $objectManager = ObjectManager::getInstance();
        $order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
        $increment_id = $order_object->getIncrementId() ;
        $store_id = $order_object->getData('store_id');
        $payment_method = $order_object->getPayment()->getMethodInstance();
		$comment = "Your Order Has Been Canceled" ;
        $order_object->addStatusToHistory("Canceled",$comment, true)->save();
		$order_object->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED)->save(); 
        $order_object->cancel()->save();
		$orderCommentSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
        //$orderCommentSender->send($order_object, $notify='1' , $comment);// $comment yout comment
		echo 'Order Has Been Cancelled. <br/><br/>';
			
		echo '<a href="' . $this->getUrl('sales/order/view/', ['order_id'=>$order_object->getId()]) . '">[ Go Back To Order Page ]</a>';
		die();
	}

    protected function _isAllowed() {
        return true;
    }
}
