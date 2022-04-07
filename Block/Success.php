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

    private $order;
    private $checkoutSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
    }

   public function one()
   {
       return 'Estamos en el bloque 5848676784759494';
   }

   public function refDePago()
   {
        //$this->instructions = $this->getConfigData('instructions');
        //return $this->instructions;
        return $this->getInformationOrder()['_paychash_pay_autorization_token']; //$this->getInformationOrder());//aqui va la referncia de pago para mostrar en succes.phtml
   }

   /*public function obtenerInstruct()
   {
       return  $this->payment->getInstructions();
   }*/
   public function getLastOrderId(){
     return $this->checkoutSession->getData('last_order_id');
   }
   
   public function getLastOrder(){
      return $this->order->load($this->getLastOrderId());
   }

   public function getInformationOrder(){
       return $this->getLastOrder()->getPayment()->getAdditionalInformation();
   }
}