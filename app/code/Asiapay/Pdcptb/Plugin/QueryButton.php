<?php 

namespace Asiapay\Pdcptb\Plugin;
use Magento\Framework\App\ObjectManager;

class QueryButton{

    public function beforeGetOrderId(\Magento\Sales\Block\Adminhtml\Order\View $subject){
    	$objectManager = ObjectManager::getInstance();
    	$serverurl = $_SERVER['REQUEST_URI'];
    	$urlRequest = explode("/",$serverurl);
    	$orderidKey = array_search('order_id', $urlRequest);
    	$order_id = $urlRequest[$orderidKey+1];
    	$order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
    	$request = $objectManager->create('\Magento\Framework\App\Request\Http');
    	$helper = $objectManager->create('\Magento\Backend\Helper\Data');
    	$payment_method = $order_object->getPayment()->getData('method');
    	$url = $helper->getUrl('pdcptb/adminpdcptb/query/',['order_id'=>$order_id]);
    	if($payment_method == 'pdcptb'){
    		$subject->addButton(
                'queryPaydollar',
                ['label' => __('[---Paydollar Query Payment Status---]'), 'onclick' => "setLocation('".$url."')", 'class' => 'edit primary'],
                -1
            );
        return null;
    	}
    	else{
    		
    	}
        
    }
}