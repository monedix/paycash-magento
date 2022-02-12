<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Paycash\Pay\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Payment
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paycash_pay';

    protected $_formBlockType = \Paycash\Pay\Block\Form\Custompayment::class;
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;
    protected $_isOffline = true;

    protected $_code = self::CODE;

    protected $active = true;
    protected $sandbox = true;
    protected $title = '';
    protected $test_apikey = '';
    protected $production_apikey = '';
    protected $country = '';
    protected $validity = '';
    protected $description = '';
    protected $instructions = '';

    protected $customerModel;
    protected $customerSession;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger_interface,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        Customer $customerModel,
        CustomerSession $customerSession,            
        array $data = []
    ) {
        parent::__construct(
            $context, $registry, $extensionFactory, $customAttributeFactory,
            $paymentData, $scopeConfig, $logger, null, null, $data     
        );
        
        $this->active = $this->getConfigData('active');
        $this->sandbox = $this->getConfigData('sandbox');
        $this->title = $this->getConfigData('title');
        $this->test_apikey = $this->getConfigData('test_apikey');
        $this->production_apikey = $this->getConfigData('production_apikey');
        $this->country = $this->getConfigData('country');
        $this->validity = $this->getConfigData('validity');
        $this->description = $this->getConfigData('description');
        $this->instructions = $this->getConfigData('instructions');

        $this->customerModel = $customerModel;
        $this->customerSession = $customerSession;

        //$this->_countryFactory = $countryFactory; //REVISAR
        //$url_base = $this->getUrlBaseOpenpay(); //REVISAR
        //$this->pdf_url_base = $url_base . "/paynet-pdf"; //REVISAR
    }
    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;
    }
    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }
        return $this;
    }
    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        return $this;
    }

    /**
     * Métodos de acceso a las variables de la configuración
     */
    public function isEnabled()
    {
        return $this->active;
    }

    public function isSandbox()
    {
        return $this->sandbox;
    }

    /*public function getTitle()
    {
        return $this->title;
    }*/

    public function getTestApikey()
    {
        return $this->test_apikey;
    }

    public function getProductionApikey()
    {
        return $this->production_apikey;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getValidity()
    {
        return $this->validity;
    }
    
    public function getDescription()
    {
        return $this->description;
    }

    public function getInstructions()
    {
        return $this->instructions;
    }

    public function createWebhook()
    {

    }
}
