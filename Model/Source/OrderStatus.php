<?php
 

namespace Paycash\Pay\Model\Source;

use \Magento\Sales\Model\Order;

/**
 * Class OrderStatus 
 */
class OrderStatus implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions on order place
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Order::STATE_PENDING_PAYMENT,
                'label' => __('Pending payment'),
            ],
            [
                'value' => Order::STATE_PROCESSING,
                'label' => __('Processing'),
            ]
        ];
    }
}
