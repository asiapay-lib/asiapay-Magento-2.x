<?php


/**
 * Pdcptb Checkout Controller
 *
 */
namespace Asiapay\Pdcptb\Controller\Pdcptb;

abstract class AbstractPdcptb extends \Magento\Framework\App\Action\Action
{

    public function __construct(\Magento\Framework\App\Action\Context $context)
    {


        parent::__construct($context);
    }

	const PARAM_NAME_REJECT_URL = 'reject_url';
	
    protected function _expireAjax()
    {
        if (!\Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Checkout\Model\Session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }



    

}
