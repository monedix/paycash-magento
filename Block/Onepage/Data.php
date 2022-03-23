<?php

namespace Paycash\Pay\Helper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
class Data extends AbstractHelper
{
    protected $customerRepository;
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession
        )
    {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }
    public function getOrderNumberValue()
    {
        $customerId  = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);
        return $customer->getData('trade_ordernumber');
    }
}