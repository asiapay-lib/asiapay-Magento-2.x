<?php


namespace Asiapay\Pdcptb\Model\Pdcptb;

use Magento\Framework\Session\SessionManager;

class Session extends SessionManager
{
    public function __construct()
    {
        $this->init('pdcptb');
    }
}
