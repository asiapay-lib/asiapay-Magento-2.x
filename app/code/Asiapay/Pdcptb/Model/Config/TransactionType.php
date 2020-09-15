<?php

namespace Asiapay\Pdcptb\Model\Config;
 
class TransactionType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '01', 'label' => __('Goods/ Service Purchase')],
            ['value' => '03', 'label' => __('Check Acceptance')],
            ['value' => '10', 'label' => __('Account Funding')],
            ['value' => '11', 'label' => __('Quasi-Cash Transaction')],
            ['value' => '28', 'label' => __('Prepaid Activation and Load')]
        ];
    }
}