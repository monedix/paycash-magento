<?php
/** 
 * @category    Payments
 * @package     Paycash
 * @author      Realvirtual
 * @copyright   Realvirtual (http://realvirtual.com.mx)
 * @license     http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0
 */


namespace Paycash\Stores\Model\Source;

use \Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @codeCoverageIgnore
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
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
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
