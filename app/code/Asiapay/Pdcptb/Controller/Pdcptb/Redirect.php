<?php

namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
//use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
//use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Asiapay\Pdcptb\Model\Pdcptb;

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

    public function __construct(Context $context, 
        //RawFactory $resultRawFactory, 
        LayoutFactory $viewLayoutFactory,
        //RedirectFactory $resultRedirectFactory,
        PageFactory $resultPageFactory, 
        Pdcptb $modelPdcptb
        ){

    	$this->_modelPdcptb = $modelPdcptb;
        //$this->_resultRawFactory = $resultRawFactory;
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
        //$this->_resultRawFactory->create()->setContents($this->_viewLayoutFactory->create()->createBlock('Asiapay\Pdcptb\Block\Redirect')->toHtml());
        $session->unsQuoteId(); 

        //get all parameters.. 
        /*$param = [
        'merchantid' => $this->getConfigData('merchant_id')
        
        ];*/
        //echo $this->_modelPdcptb->getUrl();
        $html = '<html><body>';
        $html.= 'You will be redirected to the payment gateway in a few seconds.';
        //$html.= $form->toHtml();
        $html.= '<script type="text/javascript">
require(["jquery", "prototype"], function(jQuery) {
document.getElementById("pdcptb_checkout").submit();
});
</script>';
        $html.= '</body></html>';
        echo $html;
        //$this->_modelPdcptb->getCheckoutFormFields();
        //$this->resultPageFactory->create();
        $params = $this->_modelPdcptb->getCheckoutFormFields();
        //return $resultPage;
        //sleep(25);
        //echo "5 sec up. redirecting begin";
        $result = $this->resultRedirectFactory->create();
		//$result = $this->resultRedirectFactory;
    	$result->setPath($this->_modelPdcptb->getUrl()."?".http_build_query($params));
    	//echo($this->_modelPdcptb->getUrl().http_build_query($params));
    	//return $result;
        header('Refresh: 4; URL='.$this->_modelPdcptb->getUrl()."?".http_build_query($params));
		//$this->_redirect($this->_modelPdcptb->getUrl(),$params);
		//header('Refresh: 10; URL=https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp');
    
}}
