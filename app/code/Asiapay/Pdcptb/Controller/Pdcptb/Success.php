<?php

namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class Success extends AbstractPdcptb
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
     * Where Pdcptb returns.
     * Pdcptb currently always returns the same code so there is little point
     * in attempting to process it.
     */
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
        $session->setQuoteId($session->getPdcptbQuoteId(true));
        
        // Set the quote as inactive after returning from Pdcptb
        ObjectManager::getInstance()->get('Magento\Checkout\Model\Session')->getQuote()->setIsActive(false)->save();
		
        $order = $this->_modelOrderFactory->create();
        $order->load(ObjectManager::getInstance()->get('Magento\Checkout\Model\Session')->getLastOrderId());
    	
        //Either datafeed or this successAction will set the state from Pending to Processing
        //$order->setState(Order::STATE_PROCESSING, true);	
	    //$order->save();
        
	    // Send a confirmation email to customer
        //if($order->getId()){
            //$order->sendNewOrderEmail();
        //}

        ObjectManager::getInstance()->get('Magento\Checkout\Model\Session')->unsQuoteId();
		
    	
        $this->_redirect('checkout/onepage/success');
    }
}
