<?php

namespace Paycash\Pay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class OrderPlaced implements ObserverInterface
{
 protected $logger;

 public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
 }

 public function execute(\Magento\Framework\Event\Observer $observer)
 {
    try 
    {
        $order = $observer->getEvent()->getOrder();        
        $total = $order->getGrandTotal();
        $this->logger->info($total);
        return true;
        
    }
    catch (\Exception $e)
    {
        $this->logger->info($e->getMessage());
    }
 }
}