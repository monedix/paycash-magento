<?php

namespace Paycash\Pay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
//use Magento\Framework\Event\Observer;

//use Paycash\Pay\Model\Payment as Payment;

class OrderPlaced implements ObserverInterface
{
//protected $payment;
 protected $logger;

 public function __construct(/*Payment $payment, */LoggerInterface $logger) {
    // $this->payment = $payment;
 $this->logger = $logger;
 }

 public function execute(\Magento\Framework\Event\Observer $observer)
 {
    try 
    {
        echo 'SE EJECUTO LA FUNCION EXECUTE 0';
        $this->logger->info("SE EJECUTO LA FUNCION EXECUTE 1");
        $order = $observer->getEvent()->getOrder();

        $this->logger->info("SE EJECUTO LA FUNCION EXECUTE 2");
        $total = $order->getGrandTotal();
        $this->logger->info($total);
        return true;
        //return $this->payment->orderPlaced($order);
    }
    catch (\Exception $e)
    {
        $this->logger->info("SE EJECUTO LA FUNCION EXECUTE CON ERROR");
        $this->logger->info($e->getMessage());
    }
 }
}