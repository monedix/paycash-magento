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
    protected $_urlInterface;
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
        \Magento\Framework\UrlInterface $urlInterface, 
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
        $this->_urlInterface = $urlInterface;
        $this->logger = $logger_interface;
        $this->_transportBuilder = $transportBuilder;

        //$this->_countryFactory = $countryFactory; //REVISAR
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
        //$payment->setAdditionalInformation('_paychash_pay_day_limit', 3);
        //$payment->setAdditionalInformation('_paychash_pay_autorization_token', 'aqui va el token');
        /* Otra forma de colocar los datos adicionales */
        $info = $this->getInfoInstance();//verificar para mandar a succes phph
        $info->setCcType('Visa');
        $info->setAdditionalInformation('card', 'un card');
        
        $order = $payment->getOrder();

        /**********************************************************************
         * Aqui va la petición a la API
         **********************************************************************/
        $this->setLog('Init API request to Paycash process ...');
        $paycashps_test_key =  $this->getTestApikey();
		$paycashps_production_key = $this->getProductionApikey();

        $test_urlObtenerToken = 'https://1557zh6n42.execute-api.us-east-2.amazonaws.com/sb/v1/authre';
        $test_urlObtenerReferencia = 'https://1557zh6n42.execute-api.us-east-2.amazonaws.com/sb/v1/reference';

		$produccion_urlObtenerToken = 'https://sb-api-global-emisor.paycashglobal.com/v1/authre?country=';		
		$produccion_urlObtenerReferencia = 'https://sb-api-global-emisor.paycashglobal.com/v1/reference';

        //$this->setLog('INICIO REF DE PAGO=====================================================================');
        //$this->setLog(json_encode($payment->getOrder()));
        //$this->setLog(json_encode($payment->getOrder()->getData()));
    
        $testmode = $this->isSandbox();

        $country = $this->getCountry();
        $this->setLog($country);

        $vigenciaEnDias = $this->getValidity();

        $totalOrden = $order->getGrandTotal();
        //$this->setLog($totalOrden);

        //$otroTotal = json_decode($order->getData());
        //$this->setLog($otroTotal->total_due);

        $ordenID = $order->getIncrementId();


        $this->setLog('Debug getSTOREMANAGERDATA INICIO ...');
        $this->setLog($this->_storeManager->getStore()->getId());
        $this->setLog($this->_storeManager->getStore()->getBaseUrl());
        $this->setLog($this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB));
        $this->setLog($this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK));
        $this->setLog($this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA));
        $this->setLog($this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC));
        $this->setLog($this->_storeManager->getStore()->getUrl('product/33'));
        $this->setLog($this->_storeManager->getStore()->getCurrentUrl(false));
        $this->setLog($this->_storeManager->getStore()->getBaseMediaDir());
        $this->setLog($this->_storeManager->getStore()->getBaseStaticDir());
        $this->setLog('Debug getSTOREMANAGERDATA FIN ...');


        $this->setLog('Debug getURLINTERFACE INICIO ...');
        $this->setLog($this->_urlInterface->getCurrentUrl());
        $this->setLog($this->_urlInterface->getUrl());
        $this->setLog($this->_urlInterface->getUrl('helloworld/general/enabled'));
        $this->setLog($this->_urlInterface->getBaseUrl());
        $this->setLog('Debug getURLINTERFACE FIN ...');

        /*$this->setLog($order);
        foreach ($order as $key => $value) {
            //echo "$key => $value\n";
            $this->setLog($key);
            $this->setLog($value);
        }*/        
       
        $apiKeyGral = ($testmode) ? $paycashps_test_key : $paycashps_production_key;

        if($apiKeyGral != '')
		{
            $urlObtenerToken = $test_urlObtenerToken;
			$urlObtenerReferencia = $test_urlObtenerReferencia;
            if($testmode != '1')
            {
                $urlObtenerToken = $produccion_urlObtenerToken.$country;
                $urlObtenerReferencia = $produccion_urlObtenerReferencia;
            }
            $headers = array
            (
                'Content-Type : application/json',
                'key : '.$apiKeyGral
            );

            if (!function_exists('curl_version'))
            {
                $this-> setLog('Error no se puede proceder a conectar con el servicio de PayCash porque no ha habilitado CURL para PHP .');
            }
            else
            {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlObtenerToken);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0); 
                $this-> setLog('Exec peticion token para ref de pago...');
                $data = curl_exec($ch);
                curl_close($ch);
                $body = json_decode($data);

                if($body->ErrorCode != 0)
                {
                    $this-> setLog($body->ErrorCode);
                    throw new \Magento\Framework\Exception\LocalizedException(__('Error al obtener token.'));
                }
                else
                {
                    $this-> setLog('Exec peticion token success');
                    $token = $body->Authorization;
                    $ExpirationDate = date('Y-m-d', strtotime(' + '.$vigenciaEnDias.' days'));
                    $this-> setLog($ExpirationDate);

                    $parametroPais = '';
                    
                    if($testmode != '1')
                    {
                        $parametroPais = '"country" : "'.$country.'",';
                    }
                    
                    $ch = curl_init();

                    curl_setopt_array($ch, array(
                        CURLOPT_URL => $urlObtenerReferencia,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{'.$parametroPais.'
                            "Amount": "'.strval($totalOrden).'",
                            "ExpirationDate": "'.strval($ExpirationDate).'",
                            "Value": "'.strval($ordenID).'",
                            "Type": "true"
                        }',
                        CURLOPT_HTTPHEADER => array(
                        'authorization: '.$token,
                        'Content-Type: application/json'
                        ),
                    ));
                    $this-> setLog('Exec peticion paycash ref de pago...');
                    $data = curl_exec($ch);
                    curl_close($ch);
                    
                    $body = json_decode($data);
                    
                    if($body->ErrorCode != 0)
                    {
                        $this-> setLog('ALGUN ERROR AL PEDIR PETICION DE PAYCASH');
                        throw new \Magento\Framework\Exception\LocalizedException(__('Error al obtener referencia de pago.'));
                    }
                    else
                    {
                        //$urlTemporal = '';
                        $urlTemporal = $this->_urlInterface->getBaseUrl();
                        $Reference = $body->Reference;
                        //$this-> setLog('Exec peticion paycash ref de pago sucess');
                        //$this->setLog('PRUEBA DE URL Y CREACiON DE BARCODE var log');
                        //$this->setLog(BP . '/var/log/'.$ordenID.'.png');

                        /*try{

                            $codeBarr1 = $this->barcode(BP . '/var/log/'.$ordenID.'.png', $Reference, '90');
                        }
                        catch(\Exception $e){
                            $this->setLog("error creando barcode 1:".$e->getMessage());
                        }*/

                        //$this->setLog('PRUEBA DE URL Y CREACiON DE BARCODE app temp');
                        //$this->setLog(BP . '/app/code/Paycash/Pay/TempImgBarCode/'.$ordenID.'.png');
                        $urlLogoBarCode = '';
                        try{
                            $oID = (int)$ordenID;
                            $codeBarr2 = $this->barcode(BP . '/app/code/Paycash/Pay/TempImgBarCode/'.$oID.'.png', $Reference, '90');
                            $this->setLog($codeBarr2);
                            
                            $urlLogoBarCode = $this->_urlInterface->getBaseUrl() . '/app/code/Paycash/Pay/TempImgBarCode/'.$oID.'.png';
                            $this->setLog($urlLogoBarCode);
                        }
                        catch(\Exception $e){
                            $this->setLog("error creando barcode 2:".$e->getMessage());
                        }
                        
                        $logo = '';
                        if($country == 'COL')
                            $logo = 'https://paycash-storage.s3.amazonaws.com/PCDocs/COL/colombia.jpg';
                        else if($country == 'CRI')
                            $logo = 'https://paycash-storage.s3.amazonaws.com/PCDocs/CRI/costarica.jpg';
                        else if($country == 'ECU')
                            $logo = 'https://paycash-storage.s3.amazonaws.com/PCDocs/ECU/ecuador.jpg';
                        else if($country == 'MEX')
                            $logo = 'https://paycash-storage.s3.amazonaws.com/PCDocs/MEX/mexico.jpg';
                        else if($country == 'PER')
                            $logo = 'https://paycash-storage.s3.amazonaws.com/PCDocs/PER/peru.jpg';

                        $prueba3 = 'algoDe Mensaje';
                        $prueba1 = $this->getInstructions();
                        $dataforemail = [
                            '_paychash_pay_day_limit' => $ExpirationDate,
                            '_paychash_pay_autorization_token' => $Reference,
                            'instruccionesTres' => $prueba3,
                            '_paycash_pay_instrucciones' => $prueba1,
                            '_paycash_pay_logo' => $logo,
                            '_paycash_pay_urlLogoBarCode' => $urlLogoBarCode
                        ];

                        /*$payment->setAdditionalInformation('_paychash_pay_day_limit', 3); YA se llamó anteriormente */
                        $payment->setAdditionalInformation('_paychash_pay_autorization_token', $Reference);
                        $payment->setAdditionalInformation('instruccionesTres', $prueba3);
                        $payment->setAdditionalInformation('_paycash_pay_instrucciones', $prueba1);
                        $payment->setAdditionalInformation('_paycash_pay_logo', $logo);
                        $payment->setAdditionalInformation('_paycash_pay_urlLogoBarCode', $urlLogoBarCode);
                        $payment->setAdditionalInformation('_paychash_pay_day_limit', $ExpirationDate);
                        //$payment->setAdditionalInformation('_paycash_data', json_encode($dataforemail));

                        $response = '12345678910';

                        $message = 'Este es un mensaje en donde se puede agregar el id de transacción o el id de la orden';
                        
                        //$state = \Magento\Sales\Model\Order::STATE_NEW;
                        //$payment->setPreparedMessage($message);
                        $payment->setTransactionId($response)->setPreparedMessage($message)->setIsTransactionClosed(0);
                        //$payment->getOrder()->setState($state)->setStatus($state);
                        //$this->setLog(json_encode($payment->getOrder()->getData()));
                        $this-> setLog('Enviando email...');
                        $this->sendEmail($order, $dataforemail);
                        $this-> setLog('Email enviado...');
                    }
                }
            }
        }
        else
		{
            throw new \Magento\Framework\Exception\LocalizedException(__('No se puede proceder a conectar con el servicio de PayCash favor de verificar la configuración de conexión.'));
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

    /*public function obtenerRefPayCash()
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
    }*/

    function barcode( $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1 ) {
		$code_string = "";
		// Translate the $text into barcode the correct $code_type
		if ( in_array(strtolower($code_type), array("code128", "code128b")) ) {
			$chksum = 104;
			// Must not change order of array elements as the checksum depends on the array's key to validate final code
			$code_array = array(" "=>"212222","!"=>"222122","\""=>"222221","#"=>"121223","$"=>"121322","%"=>"131222","&"=>"122213","'"=>"122312","("=>"132212",")"=>"221213","*"=>"221312","+"=>"231212",","=>"112232","-"=>"122132","."=>"122231","/"=>"113222","0"=>"123122","1"=>"123221","2"=>"223211","3"=>"221132","4"=>"221231","5"=>"213212","6"=>"223112","7"=>"312131","8"=>"311222","9"=>"321122",":"=>"321221",";"=>"312212","<"=>"322112","="=>"322211",">"=>"212123","?"=>"212321","@"=>"232121","A"=>"111323","B"=>"131123","C"=>"131321","D"=>"112313","E"=>"132113","F"=>"132311","G"=>"211313","H"=>"231113","I"=>"231311","J"=>"112133","K"=>"112331","L"=>"132131","M"=>"113123","N"=>"113321","O"=>"133121","P"=>"313121","Q"=>"211331","R"=>"231131","S"=>"213113","T"=>"213311","U"=>"213131","V"=>"311123","W"=>"311321","X"=>"331121","Y"=>"312113","Z"=>"312311","["=>"332111","\\"=>"314111","]"=>"221411","^"=>"431111","_"=>"111224","\`"=>"111422","a"=>"121124","b"=>"121421","c"=>"141122","d"=>"141221","e"=>"112214","f"=>"112412","g"=>"122114","h"=>"122411","i"=>"142112","j"=>"142211","k"=>"241211","l"=>"221114","m"=>"413111","n"=>"241112","o"=>"134111","p"=>"111242","q"=>"121142","r"=>"121241","s"=>"114212","t"=>"124112","u"=>"124211","v"=>"411212","w"=>"421112","x"=>"421211","y"=>"212141","z"=>"214121","{"=>"412121","|"=>"111143","}"=>"111341","~"=>"131141","DEL"=>"114113","FNC 3"=>"114311","FNC 2"=>"411113","SHIFT"=>"411311","CODE C"=>"113141","FNC 4"=>"114131","CODE A"=>"311141","FNC 1"=>"411131","Start A"=>"211412","Start B"=>"211214","Start C"=>"211232","Stop"=>"2331112");
			$code_keys = array_keys($code_array);
			$code_values = array_flip($code_keys);
			for ( $X = 1; $X <= strlen($text); $X++ ) {
				$activeKey = substr( $text, ($X-1), 1);
				$code_string .= $code_array[$activeKey];
				$chksum=($chksum + ($code_values[$activeKey] * $X));
			}
			$code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];

			$code_string = "211214" . $code_string . "2331112";
		} elseif ( strtolower($code_type) == "code128a" ) {
			$chksum = 103;
			$text = strtoupper($text); // Code 128A doesn't support lower case
			// Must not change order of array elements as the checksum depends on the array's key to validate final code
			$code_array = array(" "=>"212222","!"=>"222122","\""=>"222221","#"=>"121223","$"=>"121322","%"=>"131222","&"=>"122213","'"=>"122312","("=>"132212",")"=>"221213","*"=>"221312","+"=>"231212",","=>"112232","-"=>"122132","."=>"122231","/"=>"113222","0"=>"123122","1"=>"123221","2"=>"223211","3"=>"221132","4"=>"221231","5"=>"213212","6"=>"223112","7"=>"312131","8"=>"311222","9"=>"321122",":"=>"321221",";"=>"312212","<"=>"322112","="=>"322211",">"=>"212123","?"=>"212321","@"=>"232121","A"=>"111323","B"=>"131123","C"=>"131321","D"=>"112313","E"=>"132113","F"=>"132311","G"=>"211313","H"=>"231113","I"=>"231311","J"=>"112133","K"=>"112331","L"=>"132131","M"=>"113123","N"=>"113321","O"=>"133121","P"=>"313121","Q"=>"211331","R"=>"231131","S"=>"213113","T"=>"213311","U"=>"213131","V"=>"311123","W"=>"311321","X"=>"331121","Y"=>"312113","Z"=>"312311","["=>"332111","\\"=>"314111","]"=>"221411","^"=>"431111","_"=>"111224","NUL"=>"111422","SOH"=>"121124","STX"=>"121421","ETX"=>"141122","EOT"=>"141221","ENQ"=>"112214","ACK"=>"112412","BEL"=>"122114","BS"=>"122411","HT"=>"142112","LF"=>"142211","VT"=>"241211","FF"=>"221114","CR"=>"413111","SO"=>"241112","SI"=>"134111","DLE"=>"111242","DC1"=>"121142","DC2"=>"121241","DC3"=>"114212","DC4"=>"124112","NAK"=>"124211","SYN"=>"411212","ETB"=>"421112","CAN"=>"421211","EM"=>"212141","SUB"=>"214121","ESC"=>"412121","FS"=>"111143","GS"=>"111341","RS"=>"131141","US"=>"114113","FNC 3"=>"114311","FNC 2"=>"411113","SHIFT"=>"411311","CODE C"=>"113141","CODE B"=>"114131","FNC 4"=>"311141","FNC 1"=>"411131","Start A"=>"211412","Start B"=>"211214","Start C"=>"211232","Stop"=>"2331112");
			$code_keys = array_keys($code_array);
			$code_values = array_flip($code_keys);
			for ( $X = 1; $X <= strlen($text); $X++ ) {
				$activeKey = substr( $text, ($X-1), 1);
				$code_string .= $code_array[$activeKey];
				$chksum=($chksum + ($code_values[$activeKey] * $X));
			}
			$code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];

			$code_string = "211412" . $code_string . "2331112";
		} elseif ( strtolower($code_type) == "code39" ) {
			$code_array = array("0"=>"111221211","1"=>"211211112","2"=>"112211112","3"=>"212211111","4"=>"111221112","5"=>"211221111","6"=>"112221111","7"=>"111211212","8"=>"211211211","9"=>"112211211","A"=>"211112112","B"=>"112112112","C"=>"212112111","D"=>"111122112","E"=>"211122111","F"=>"112122111","G"=>"111112212","H"=>"211112211","I"=>"112112211","J"=>"111122211","K"=>"211111122","L"=>"112111122","M"=>"212111121","N"=>"111121122","O"=>"211121121","P"=>"112121121","Q"=>"111111222","R"=>"211111221","S"=>"112111221","T"=>"111121221","U"=>"221111112","V"=>"122111112","W"=>"222111111","X"=>"121121112","Y"=>"221121111","Z"=>"122121111","-"=>"121111212","."=>"221111211"," "=>"122111211","$"=>"121212111","/"=>"121211121","+"=>"121112121","%"=>"111212121","*"=>"121121211");

			// Convert to uppercase
			$upper_text = strtoupper($text);

			for ( $X = 1; $X<=strlen($upper_text); $X++ ) {
				$code_string .= $code_array[substr( $upper_text, ($X-1), 1)] . "1";
			}

			$code_string = "1211212111" . $code_string . "121121211";
		} elseif ( strtolower($code_type) == "code25" ) {
			$code_array1 = array("1","2","3","4","5","6","7","8","9","0");
			$code_array2 = array("3-1-1-1-3","1-3-1-1-3","3-3-1-1-1","1-1-3-1-3","3-1-3-1-1","1-3-3-1-1","1-1-1-3-3","3-1-1-3-1","1-3-1-3-1","1-1-3-3-1");

			for ( $X = 1; $X <= strlen($text); $X++ ) {
				for ( $Y = 0; $Y < count($code_array1); $Y++ ) {
					if ( substr($text, ($X-1), 1) == $code_array1[$Y] )
						$temp[$X] = $code_array2[$Y];
				}
			}

			for ( $X=1; $X<=strlen($text); $X+=2 ) {
				if ( isset($temp[$X]) && isset($temp[($X + 1)]) ) {
					$temp1 = explode( "-", $temp[$X] );
					$temp2 = explode( "-", $temp[($X + 1)] );
					for ( $Y = 0; $Y < count($temp1); $Y++ )
						$code_string .= $temp1[$Y] . $temp2[$Y];
				}
			}

			$code_string = "1111" . $code_string . "311";
		} elseif ( strtolower($code_type) == "codabar" ) {
			$code_array1 = array("1","2","3","4","5","6","7","8","9","0","-","$",":","/",".","+","A","B","C","D");
			$code_array2 = array("1111221","1112112","2211111","1121121","2111121","1211112","1211211","1221111","2112111","1111122","1112211","1122111","2111212","2121112","2121211","1121212","1122121","1212112","1112122","1112221");

			// Convert to uppercase
			$upper_text = strtoupper($text);

			for ( $X = 1; $X<=strlen($upper_text); $X++ ) {
				for ( $Y = 0; $Y<count($code_array1); $Y++ ) {
					if ( substr($upper_text, ($X-1), 1) == $code_array1[$Y] )
						$code_string .= $code_array2[$Y] . "1";
				}
			}
			$code_string = "11221211" . $code_string . "1122121";
		}

		// Pad the edges of the barcode
		$code_length = 20;
		if ($print) {
			$text_height = 30;
		} else {
			$text_height = 0;
		}
		
		for ( $i=1; $i <= strlen($code_string); $i++ ){
			$code_length = $code_length + (integer)(substr($code_string,($i-1),1));
			}

		if ( strtolower($orientation) == "horizontal" ) {
			$img_width = $code_length*$SizeFactor;
			$img_height = $size;
		} else {
			$img_width = $size;
			$img_height = $code_length*$SizeFactor;
		}

		$image = imagecreate($img_width, $img_height + $text_height);
		$black = imagecolorallocate ($image, 0, 0, 0);
		$white = imagecolorallocate ($image, 255, 255, 255);

		imagefill( $image, 0, 0, $white );
		if ( $print ) {
			imagestring($image, 5, 31, $img_height, $text, $black );
		}

		$location = 10;
		for ( $position = 1 ; $position <= strlen($code_string); $position++ ) {
			$cur_size = $location + ( substr($code_string, ($position-1), 1) );
			if ( strtolower($orientation) == "horizontal" )
				imagefilledrectangle( $image, $location*$SizeFactor, 0, $cur_size*$SizeFactor, $img_height, ($position % 2 == 0 ? $white : $black) );
			else
				imagefilledrectangle( $image, 0, $location*$SizeFactor, $img_width, $cur_size*$SizeFactor, ($position % 2 == 0 ? $white : $black) );
			$location = $cur_size;
		}
		
		// Draw barcode to the screen or save in a file
		if ( $filepath=="" ) {
			header ('Content-type: image/png');
			imagepng($image);
			imagedestroy($image);
		} else {
			imagepng($image,$filepath);
			imagedestroy($image);		
		}
	}

    public function setLog($log)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/paycash.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($log);
    }
}
