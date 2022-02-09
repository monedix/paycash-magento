<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Paycash\Pay\Model;

/*use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;

use Openpay\Data\Client as Paycash;*/

/**
 * Class Payment
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paycash_pay';

    protected $_code = self::CODE;
    protected $pc_activo = true;
    protected $pc_pruebas = true;
    protected $pc_mensaje = '';
    protected $pc_apitestkey = '';
    protected $pc_apiproductionkey = '';
    protected $pc_pais = '';
    protected $pc_diasvigencia = '';
    protected $pc_descripcion = '';
    protected $pc_instrucciones = '';
    
    public function __construct(\Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        /*\Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        array $data = array()*/
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
            $paymentData, $scopeConfig, $logger, /*$moduleList, $localeDate, null,
            null, $data  */
            null,
            null,            
            $data     
        );
        
        $this->_countryFactory = $countryFactory; //REVISAR
        
        $this->pc_activo = $this->getConfigData('pc_activo');
        $this->pc_pruebas = $this->getConfigData('pc_pruebas');
        $this->pc_mensaje = $this->getConfigData('pc_mensaje');
        $this->pc_apitestkey = $this->getConfigData('pc_apitestkey');
        $this->pc_apiproductionkey = $this->getConfigData('pc_apiproductionkey');
        $this->pc_pais = $this->getConfigData('pc_pais');
        $this->pc_diasvigencia = $this->getConfigData('pc_diasvigencia');
        $this->pc_descripcion = $this->getConfigData('pc_descripcion');
        $this->pc_instrucciones = $this->getConfigData('pc_instrucciones');


        $url_base = $this->getUrlBaseOpenpay(); //REVISAR
        $this->pdf_url_base = $url_base . "/paynet-pdf"; //REVISAR
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








}
