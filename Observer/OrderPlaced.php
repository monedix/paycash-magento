<?php

namespace Paycash\Pay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;

use Paycash\Pay\Model\Payment as Payment;

class OrderPlaced implements ObserverInterface
{
protected $payment;
 protected $logger;

 public function __construct(Payment $payment, LoggerInterface $logger) {
     $this->payment = $payment;
 $this->logger = $logger;
 }

 public function execute(Observer $observer)
 {
    try 
    {
        $order = $observer->getEvent()->getOrder();

        $this->logger->info("SE EJECUTO LA FUNCION EXECUTE");
        $total = $order->getGrandTotal();
        $this->logger->info($total);

        return $this->payment->orderPlaced($order);
    }
    catch (\Exception $e)
    {
        $this->logger->info($e->getMessage());
    }
 }
}