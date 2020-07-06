<?php
/**
 * Adminpdcptb Controller
 *
 */

namespace Asiapay\Pdcptb\Controller\Datafeed;

abstract class AbstractDatafeed extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
	public function __construct(
	\Magento\Framework\App\Action\Context $context,
	\Magento\Framework\View\LayoutFactory $viewLayoutFactory)
    {

		parent::__construct($context);
		$this->_viewLayoutFactory = $viewLayoutFactory;
        //parent::__construct($viewLayoutFactory);
    }

	 
    protected $_viewLayoutFactory;

	function _verifyPaymentDatafeed($src, $prc, $successCode, $merchantReferenceNumber, $paydollarReferenceNumber, $currencyCode, $amount, $payerAuthenticationStatus, $secureHashSecret, $secureHash) {
		$buffer = $src . '|' . $prc . '|' . $successCode . '|' . $merchantReferenceNumber . '|' . $paydollarReferenceNumber . '|' . $currencyCode . '|' . $amount . '|' . $payerAuthenticationStatus . '|' . $secureHashSecret;
		$verifyData = sha1($buffer);
		if ($secureHash == $verifyData) { return true; }
		return false;
	}
	
	function _getIsoCurrCode($magento_currency_code) {
	switch($magento_currency_code){
	case 'HKD':
		$cur = '344';
		break;
	case 'USD':
		$cur = '840';
		break;
	case 'SGD':
		$cur = '702';
		break;
	case 'CNY':
		$cur = '156';
		break;
	case 'JPY':
		$cur = '392';
		break;		
	case 'TWD':
		$cur = '901';
		break;
	case 'AUD':
		$cur = '036';
		break;
	case 'EUR':
		$cur = '978';
		break;
	case 'GBP':
		$cur = '826';
		break;
	case 'CAD':
		$cur = '124';
		break;
	case 'MOP':
		$cur = '446';
		break;
	case 'PHP':
		$cur = '608';
		break;
	case 'THB':
		$cur = '764';
		break;
	case 'MYR':
		$cur = '458';
		break;
	case 'IDR':
		$cur = '360';
		break;
	case 'KRW':
		$cur = '410';
		break;
	case 'SAR':
		$cur = '682';
		break;
	case 'NZD':
		$cur = '554';
		break;
	case 'AED':
		$cur = '784';
		break;
	case 'BND':
		$cur = '096';
		break;
	case 'VND':
		$cur = '704';
		break;
	case 'INR':
		$cur = '356';
		break;
	default:
		$cur = '344';
	}	
	return $cur;
}
}

