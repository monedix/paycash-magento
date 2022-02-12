<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paycash\Pay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Paycash\Pay\Model\Payment as OpenpayPayment;
use Magento\Checkout\Model\Cart;

class OpenpayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'paycash_pay',        
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];
    
    /**
     * @var \Paycash\Pay\Model\Payment
     */
    protected $payment ;

    protected $cart;

    /**     
     * @param PaymentHelper $paymentHelper
     * @param OpenpayPayment $payment
     */
    public function __construct(PaymentHelper $paymentHelper, OpenpayPayment $payment, Cart $cart) {        
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
                $config['paycash_pay']['active'] = $this->payment->isActive();
                $config['paycash_pay']['sandbox'] = $this->payment->isSandbox();
                $config['paycash_pay']['title'] = $this->payment->getTitle();
                $config['paycash_pay']['test_apikey'] = $this->payment->getTestApikey();
                $config['paycash_pay']['production_apikey'] = $this->payment->getProductionApikey();
                $config['paycash_pay']['country'] = $this->payment->getCountry();
                $config['paycash_pay']['validity'] = $this->payment->getValidity();
                $config['paycash_pay']['description'] = $this->payment->getDescription();
                $config['paycash_pay']['instructions'] = $this->payment->getInstructions();
            }
        }
                
        return $config;
    }      
}
