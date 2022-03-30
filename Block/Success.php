<?php

namespace Paycash\Pay\Block;
//use Paycash\Pay\Model\Payment as OPayment;

class Success extends \Magento\Framework\View\Element\Template
{
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
        return ' ';//aqui va la referncia de pago para mostrar en succes.phtml
   }

   /*public function obtenerInstruct()
   {
       return  $this->payment->getInstructions();
   }*/
}