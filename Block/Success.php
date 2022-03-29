<?php

namespace Paycash\Pay\Block;
use Paycash\Pay\Model\Payment as Payment;

class Success extends \Magento\Framework\View\Element\Template
{
   protected $payment;
   public function __construct(Payment $payment) {
        $this->payment = $payment;
    }

   public function one()
   {
       return 'Estamos en el bloque 5848676784759494';
   }

   public function refDePago()
   {
        return $this->payment->getInstructions();
       //return '486404867fkgi343#343$5%634';
   }
}