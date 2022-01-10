<?php
/** 
 * @category    Payments
 * @package     Paycash
 * @author      Realvirtual
 * @copyright   Realvirtual (http://realvirtual.com.mx)
 * @license     http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0
 */


namespace Paycash\Stores\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Paycash\Stores\Model\Payment as Config;

/**
 * Class CreateWebhook
 */
class CreateWebhook implements ObserverInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Config $config
     * @param ManagerInterface $messageManager
     */
    public function __construct(
    Config $config, ManagerInterface $messageManager
    ) {
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    /**
     * Create Webhook
     *
     * @param Observer $observer          
     */
    public function execute(Observer $observer) {
        return $this->config->createWebhook();
    }

}
