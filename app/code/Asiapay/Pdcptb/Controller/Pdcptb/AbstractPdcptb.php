<?php


/**
 * Pdcptb Checkout Controller
 *
 */
namespace Asiapay\Pdcptb\Controller\Pdcptb;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

abstract class AbstractPdcptb extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    public function __construct(\Magento\Framework\App\Action\Context $context)
    {


        parent::__construct($context);
    }

    /** 
	 * @inheritDoc
	 */
	public function createCsrfValidationException(
		RequestInterface $request 
	): ?InvalidRequestException {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
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
