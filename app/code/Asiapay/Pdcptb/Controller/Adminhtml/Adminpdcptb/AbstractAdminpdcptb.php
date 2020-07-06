<?php
/**
 * Adminpdcptb Controller
 *
 */

namespace Asiapay\Pdcptb\Controller\Adminhtml\Adminpdcptb;

abstract class AbstractAdminpdcptb extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
	public function __construct(
	\Magento\Backend\App\Action\Context $context,
	\Magento\Framework\View\LayoutFactory $viewLayoutFactory)
    {

		parent::__construct($context);
		$this->_viewLayoutFactory = $viewLayoutFactory;
        //parent::__construct($viewLayoutFactory);
    }

	 
    protected $_viewLayoutFactory;

    protected function _httpPost($postUrl, $postData , $isRequestHeader=false)
    {    
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $postUrl);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($ch, CURLOPT_HEADER, (($isRequestHeader) ? 1 : 0));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $response = curl_exec($ch);	
	    curl_close($ch);
	    return $response;
	}
}

