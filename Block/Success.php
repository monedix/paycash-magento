<?php

namespace Paycash\Pay\Block;
//use Paycash\Pay\Model\Payment as OPayment;

class Success extends \Magento\Framework\View\Element\Template
{ 
   protected $payment ;
   //protected $instructions = '';
   /*protected $payment;
   public function __construct(OPayment $payment) {
        $this->payment = $payment;
    }*/

   public function one()
   {
       return 'Estamos en el bloque 5848676784759494';
   }

   public function refDePago()
   {
        //$this->instructions = $this->getConfigData('instructions');
        //return $this->instructions;
        return '486404867fkgi343#343$5%634';
   }

   public function obtenerInstruct()
   {
       return  $this->payment->getInstructions();
   }
}