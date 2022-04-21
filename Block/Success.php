<?php

namespace Paycash\Pay\Block;

class Success extends \Magento\Framework\View\Element\Template
{
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
        return $this->getInformationOrder()['_paychash_pay_autorization_token']; 
   }

   public function obtenerInstruct()
   {
       return  $this->getInformationOrder()['_paycash_pay_instrucciones'];
   }

   public function obtenerBarCode(){
    return  $this->getInformationOrder()['_paycash_pay_urlLogoBarCode'];
   }

   public function obtenerExpirationDt(){
       return $this->getInformationOrder()['_paychash_pay_day_limit'];
   }

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