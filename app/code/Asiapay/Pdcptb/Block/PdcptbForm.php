<?php


namespace Asiapay\Pdcptb\Block;

class PdcptbForm extends \Magento\Payment\Block\Form
{
    protected function _construct()
    {
        $this->setTemplate('Asiapay_Pdcptb::asiapay/pdcptb/form.phtml');
        parent::_construct();
    }
}
