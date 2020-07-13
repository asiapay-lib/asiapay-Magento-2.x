<?php

namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\OrderFactory;

class Failure extends AbstractPdcptb
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

    public function execute()
    {
    	$session = ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
        $session->setQuoteId($session->getPdcptbQuoteId(true));
        $objectManager=ObjectManager::getInstance();
    	// cancel order
        if ($session->getLastRealOrderId()) {
            $order_object = $objectManager->create('\Magento\Sales\Model\Order')->load($session->getLastRealOrderId());
            if ($order_object->getId()) {
                $order_object->cancel()->save();
            }
        }
        $this->_redirect('checkout/onepage/failure');
    }
}
