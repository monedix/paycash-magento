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


    protected $_canOrder = true;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    

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

    protected $_storeManager;
    protected $logger;
    protected $_transportBuilder;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Paycash\Pay\Mail\Template\TransportBuilder $transportBuilder,
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

        $this->_storeManager = $storeManager;
        $this->logger = $logger_interface;
        $this->_transportBuilder = $transportBuilder;

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
    public function authorizeNew(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        echo 'SE EJECUTO LA FUNCION EXECUTE desde payment authorize';
        var_dump($payment->getData());
        $timezone = $this->scope_config->getValue('general/locale/timezone');
        date_default_timezone_set($timezone);

        //Obtiene el objeto de la orden
        $order = $payment->getOrder();
        //Obtiene el objeto billingAddress
        $billing = $order->getBillingAddress();
        echo 'SE EJECUTO LA FUNCION EXECUTE desde payment';
        try
        {
            //Obtiene datos del cliente
            $customer_name = $billing->getFirstname();
            $customer_lastname = $billing->getLastname();
            $customer_email = $order->getCustomerEmail();

            $customer_data = array
            (
                'name' => $customer_name,
                'last_name' => $customer_lastname,
                'email' => $customer_email
            );

            //REVISAR ESTA FUNCION PARA QUE VALIDE EN BASE A DIAS
            $fecha_vigencia = date('Y-m-d\TH:i:s', strtotime('+ '.$this->validity.' hours'));

            $charge_request = array(
                'method' => 'store',
                'currency' => strtolower($order->getBaseCurrencyCode()),
                'amount' => $amount,
                'description' => sprintf('ORDER #%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                'order_id' => $order->getIncrementId(),
                'due_date' => $fecha_vigencia,
                'customer' => $customer_data
            );

            //Conexion a PayCash para obtener referencia de pago
            $referenciaDePago = "1234567890";
            //Después de la conexión a PayCash

            $payment->setTransactionId("IDPRUEBA");

            //Actualizar el estado de la orden
            $state = \Magento\Sales\Model\Order::STATE_NEW;
            $order->setState($state)->setStatus($state);

            //Guarda la referencia de pago
            $order->setExtOrderId($referenciaDePago); 
            $order->save();

            //Envío de correo al cliente
            $this->sendEmail($order);
        }
        catch (\Exception $e)
        {
            //REVISAR LAS DOS LINEAS SIGUIENTES PARA CONOCER COMO FUNCIONAN EXACTAMENTE
            $this->debugData(['exception' => $e->getMessage()]);
            $this->_logger->error(__( $e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__($this->error($e)));
        }

        $payment->setSkipOrderProcessing(true);
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

        //echo 'SE EJECUTO LA FUNCION EXECUTE desde payment capture';
        $this->setLog('SE EJECUTO LA FUNCION EXECUTE desde payment capture');
        //$this->setLog('capture: '. $payment->getData());


        /* Aun no existe la orden */
        $payment->setAdditionalInformation('_paychash_pay_day_limit', 3);
        $payment->setAdditionalInformation('_paychash_pay_autorization_token', 'aqui va el token');
        /* Otra forma de colocar los datos adicionales */
        $info = $this->getInfoInstance();
        $info->setCcType('Visa');
        $info->setAdditionalInformation('card', 'un card');
        
        $order = $payment->getOrder();

        /**********
         * Aqui va la petición a la API
         *******/

        /*if($response['status'] == 200){
            // metodo positivo
        }else{
            // respuesta negativa
            //throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }*/
        //$prueba2 =  $this->instructions;
        $prueba3 = 'algoDe Mensaje';
        $prueba1 = $this->getInstructions();
        $dataforemail = [
            '_paychash_pay_day_limit' => 3,
            '_paychash_pay_autorization_token' => 'FIR88JG433498694#77FK77JDKGJ77DKF57JDGKDNHNPLHITL6444$5',
            'instruccionesTres' => $prueba3,
            'instruccionesDeUso' => $prueba1
        ];

        $response = '12345678910';

        $message = 'Este es un mensaje en donde se puede agregar el id de transacción o el id de la orden';
        
        //$state = \Magento\Sales\Model\Order::STATE_NEW;
        //$payment->setPreparedMessage($message);
        $payment->setTransactionId($response)->setPreparedMessage($message)->setIsTransactionClosed(0);
        //$payment->getOrder()->setState($state)->setStatus($state);
        $this->setLog(json_encode($payment->getOrder()->getData()));
        $this->sendEmail($order, $dataforemail);

        /*
        var_dump($payment->getData());
        $timezone = $this->scope_config->getValue('general/locale/timezone');
        date_default_timezone_set($timezone);
        */
        /*
        //Obtiene el objeto de la orden
        $order = $payment->getOrder();
        //Obtiene el objeto billingAddress
        $billing = $order->getBillingAddress();
        echo 'SE EJECUTO LA FUNCION EXECUTE desde payment';
        try
        {
            //Obtiene datos del cliente
            $customer_name = $billing->getFirstname();
            $customer_lastname = $billing->getLastname();
            $customer_email = $order->getCustomerEmail();

            $customer_data = array
            (
                'name' => $customer_name,
                'last_name' => $customer_lastname,
                'email' => $customer_email
            );

            //REVISAR ESTA FUNCION PARA QUE VALIDE EN BASE A DIAS
            $fecha_vigencia = date('Y-m-d\TH:i:s', strtotime('+ '.$this->validity.' hours'));

            $charge_request = array(
                'method' => 'store',
                'currency' => strtolower($order->getBaseCurrencyCode()),
                'amount' => $amount,
                'description' => sprintf('ORDER #%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                'order_id' => $order->getIncrementId(),
                'due_date' => $fecha_vigencia,
                'customer' => $customer_data
            );

            //Conexion a PayCash para obtener referencia de pago
            $referenciaDePago = "1234567890";
            //Después de la conexión a PayCash

            $payment->setTransactionId("IDPRUEBA");

            //Actualizar el estado de la orden
            $state = \Magento\Sales\Model\Order::STATE_NEW;
            $order->setState($state)->setStatus($state);

            //Guarda la referencia de pago
            $order->setExtOrderId($referenciaDePago); 
            $order->save();

            //Envío de correo al cliente
            $this->sendEmail($order);
        }
        catch (\Exception $e)
        {
            //REVISAR LAS DOS LINEAS SIGUIENTES PARA CONOCER COMO FUNCIONAN EXACTAMENTE
            $this->debugData(['exception' => $e->getMessage()]);
            $this->_logger->error(__( $e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__($this->error($e)));
        }

        $payment->setSkipOrderProcessing(true);
        */
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
        $base_url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $uri = $base_url."paycash/index/webhook";
    }

    public function orderNew(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $timezone = $this->scope_config->getValue('general/locale/timezone');
        date_default_timezone_set($timezone);

        //Obtiene el objeto de la orden
        $order = $payment->getOrder();
        //Obtiene el objeto billingAddress
        $billing = $order->getBillingAddress();
        echo 'SE EJECUTO LA FUNCION EXECUTE desde payment';
        try
        {
            //Obtiene datos del cliente
            $customer_name = $billing->getFirstname();
            $customer_lastname = $billing->getLastname();
            $customer_email = $order->getCustomerEmail();

            $customer_data = array
            (
                'name' => $customer_name,
                'last_name' => $customer_lastname,
                'email' => $customer_email
            );

            //REVISAR ESTA FUNCION PARA QUE VALIDE EN BASE A DIAS
            $fecha_vigencia = date('Y-m-d\TH:i:s', strtotime('+ '.$this->validity.' hours'));

            $charge_request = array(
                'method' => 'store',
                'currency' => strtolower($order->getBaseCurrencyCode()),
                'amount' => $amount,
                'description' => sprintf('ORDER #%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                'order_id' => $order->getIncrementId(),
                'due_date' => $fecha_vigencia,
                'customer' => $customer_data
            );

            //Conexion a PayCash para obtener referencia de pago
            $referenciaDePago = "1234567890";
            //Después de la conexión a PayCash

            $payment->setTransactionId("IDPRUEBA");

            //Actualizar el estado de la orden
            $state = \Magento\Sales\Model\Order::STATE_NEW;
            $order->setState($state)->setStatus($state);

            //Guarda la referencia de pago
            $order->setExtOrderId($referenciaDePago); 
            $order->save();

            //Envío de correo al cliente
            $this->sendEmail($order);
        }
        catch (\Exception $e)
        {
            //REVISAR LAS DOS LINEAS SIGUIENTES PARA CONOCER COMO FUNCIONAN EXACTAMENTE
            $this->debugData(['exception' => $e->getMessage()]);
            $this->_logger->error(__( $e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__($this->error($e)));
        }

        $payment->setSkipOrderProcessing(true);
        return $this;
    }

    public function sendEmail($order, $dataforemail = array())
    {    
        try
        {

            $templateId = 'paycash_pdf_template';
            $email = 'demo@demo.com'; //$this->scope_config->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
            $name  = 'demo'; //$this->scope_config->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE);
            $toEmail = $order->getCustomerEmail();  

            $template_vars = array(
                'title' => 'Tu referencia de pago | Orden #'.$order->getIncrementId(),
                'adicional' => $dataforemail,
            );

            $storeId = $this->_storeManager->getStore()->getId();
            $from = array('email' => $email, 'name' => $name);
            
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];

            $this->logger->debug('#sendEmail', array('$from' => $from, '$toEmail' => $toEmail));

            $transportBuilderObj = $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($template_vars)
            ->setFrom($from)
            ->addTo($toEmail)
            //->addAttachment($pdf, 'recibo_pago.pdf', 'application/octet-stream')
            ->getTransport();
            $transportBuilderObj->sendMessage(); 
            return;
        } 
        catch (\Magento\Framework\Exception\MailException $me)
        {            
            $this->logger->error('#MailException', array('msg' => $me->getMessage()));
        }
        catch (\Exception $e)
        {            
            $this->logger->error('#Exception', array('msg' => $e->getMessage()));
        }
    }  

    public function orderPlaced($order)
    {
        $timezone = $this->scope_config->getValue('general/locale/timezone');
        date_default_timezone_set($timezone);

        //Obtiene el objeto billingAddress
        $billing = $order->getBillingAddress();

        try
        {
            //Obtiene datos del cliente
            $customer_name = $billing->getFirstname();
            $customer_lastname = $billing->getLastname();
            $customer_email = $order->getCustomerEmail();

            $customer_data = array
            (
                'name' => $customer_name,
                'last_name' => $customer_lastname,
                'email' => $customer_email
            );

            //REVISAR ESTA FUNCION PARA QUE VALIDE EN BASE A DIAS
            $fecha_vigencia = date('Y-m-d\TH:i:s', strtotime('+ '.$this->validity.' hours'));

            $charge_request = array(
                'method' => 'store',
                'currency' => strtolower($order->getBaseCurrencyCode()),
                'amount' => $amount,
                'description' => sprintf('ORDER #%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                'order_id' => $order->getIncrementId(),
                'due_date' => $fecha_vigencia,
                'customer' => $customer_data
            );

            //Conexion a PayCash para obtener referencia de pago
            //Codigo nuevo de conexion a Paycash
            $referenciaDePago = "1234567890";
            //Después de la conexión a PayCash

            $payment->setTransactionId("IDPRUEBA");

            //Actualizar el estado de la orden
            $state = \Magento\Sales\Model\Order::STATE_NEW;
            $order->setState($state)->setStatus($state);

            //Guarda la referencia de pago
            $order->setExtOrderId($referenciaDePago); 
            $order->save();

            //Envío de correo al cliente
            $this->sendEmail($order);
        }
        catch (\Exception $e)
        {
            //REVISAR LAS DOS LINEAS SIGUIENTES PARA CONOCER COMO FUNCIONAN EXACTAMENTE
            $this->debugData(['exception' => $e->getMessage()]);
            $this->_logger->error(__( $e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__($this->error($e)));
        }

        $payment->setSkipOrderProcessing(true);
        return $this;
    }

    public function obtenerRefPayCash()
    {
        $refPago = "";
        $refToken = "";
        $test_urlObtenerToken = 'https://1557zh6n42.execute-api.us-east-2.amazonaws.com/sb/v1/authre';
		$produccion_urlObtenerToken = 'https://sb-api-global-emisor.paycashglobal.com/v1/authre?country=';
		$test_urlObtenerReferencia = 'https://1557zh6n42.execute-api.us-east-2.amazonaws.com/sb/v1/reference';
		$produccion_urlObtenerReferencia = 'https://sb-api-global-emisor.paycashglobal.com/v1/reference';
        //'paycashps_test_key', '5d9d90c5013111ecaf8b0afe8920d1ea'
		//'paycashps_production_key', '5d9d90c5013111ecaf8b0afe8920d1ea'
        //$value_paycashps_test_key = (string) Tools::getValue('paycashps_test_key');
		//$value_paycashps_production_key = (string) Tools::getValue('paycashps_production_key');
    }
}
