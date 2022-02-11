<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paycash\Pay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Paycash\Pay\Model\Payment as PaycashPayment;
use Magento\Checkout\Model\Cart;

class DescriptionConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        //Custompayment::CUSTOM_PAYMENT_CODE,
        'paycash_pay',  
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    protected $payment;
    protected $cart;

    /**     
     * @param PaymentHelper $paymentHelper
     * @param PaycashPayment $payment
     */
    public function __construct(PaymentHelper $paymentHelper, PaycashPayment $payment) {        
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
        $this->cart = $cart;
        $this->payment = $payment;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {                
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['paycash_pay']['description'] = $this->payment->getDescription();
            }
        }
                
        return $config;
    }

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    /*public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['paycash_pay']['description'][$code] = $this->getDescription($code);
            }
        }
        return $config;
    }*/

    /**
     * Get description text from config
     *
     * @param string $code
     * @return string
     */
    /*protected function getDescription($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getDescription()));
    }*/
}