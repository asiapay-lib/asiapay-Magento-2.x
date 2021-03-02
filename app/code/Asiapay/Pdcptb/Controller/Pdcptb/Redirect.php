<?php

namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Asiapay\Pdcptb\Model\Pdcptb;
use Magento\Framework\App\Request\Http;

class Redirect extends AbstractPdcptb
{
    /**
     * @var RawFactory
     */
    //protected $_resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    /**
     * @var RedirectFactory
     */
    //protected $_resultRedirectFactory;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var Pdcptb Model
     */
    protected $_modelPdcptb;

    protected $request;

    public function __construct(
        Http $request,
        Context $context, 
        LayoutFactory $viewLayoutFactory,
        PageFactory $resultPageFactory, 
        Pdcptb $modelPdcptb
        ){
        
        $this->request = $request;
		$this->_resultFactory = $context->getResultFactory();
    	$this->_modelPdcptb = $modelPdcptb;
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_resultRedirectFactory = $context->getResultRedirectFactory();
        $this->_resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * When a customer chooses Pdcptb on Checkout/Payment page
     */
    public function execute()
    {
		$session = ObjectManager::getInstance()->get('Magento\Checkout\Model\Session');
		
        $session->setPdcptbQuoteId($session->getQuoteId());
        $session->unsQuoteId(); 
		
		$params = $this->_modelPdcptb->getCheckoutFormFields();
		
		$page = $this->_resultFactory->create(ResultFactory::TYPE_PAGE);

		/** @var Template $block */
		$block = $page->getLayout()->getBlock('pdcptb.payfrom');
		$block->setData('pay_url', $this->_modelPdcptb->getUrl());
		$block->setData('pay_data', $params);

        return $page;

	}
}
