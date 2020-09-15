<?php

namespace Asiapay\Pdcptb\Model\Config;
 
class ChallengePreference implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '01', 'label' => __('No preference')],
            ['value' => '02', 'label' => __('No challenge requested *')],
            ['value' => '03', 'label' => __('Challenge requested (Merchant preference)')],
            ['value' => '04', 'label' => __('Challenge requested (Mandate)')],
            ['value' => '05', 'label' => __('No challenge requested (transactional risk analysis is already performed) *')],
            ['value' => '06', 'label' => __('No challenge requested (Data share only)*')],
            ['value' => '07', 'label' => __('No challenge requested (strong consumer authentication is already performed) *')],
            ['value' => '08', 'label' => __('No challenge requested (utilise whitelist exemption if no challenge required) *')],
            ['value' => '09', 'label' => __('Challenge requested (whitelist prompt requested if challenge required)')],
        ];
    }
}