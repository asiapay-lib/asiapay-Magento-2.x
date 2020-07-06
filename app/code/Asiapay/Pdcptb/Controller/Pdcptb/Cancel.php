<?php

namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\OrderFactory;

class Cancel extends AbstractPdcptb
{
    /**
     * @var OrderFactory
     */
    protected $_modelOrderFactory;

    public function __construct(Context $context, 
        OrderFactory $modelOrderFactory)
    {
        $this->_modelOrderFactory = $modelOrderFactory;

        parent::__construct($context);
    }

    /**
     * When a customer cancels payment from Pdcptb.
     */
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
        $session->setQuoteId($session->getPdcptbQuoteId(true));
        
    	// cancel order
        if ($session->getLastRealOrderId()) {
            $order_object = $this->_modelOrderFactory->create()->loadByIncrementId($session->getLastRealOrderId());
            if ($order_object->getId()) {
                $order_object->cancel()->save();
                $comment = "Your Order Has Been Canceled" ;
                $orderCommentSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
                $orderCommentSender->send($order_object, $notify='1' , $comment);// Send Cancel comment to customer            
            }
        }
        $this->_redirect('checkout/cart');
     }
}
