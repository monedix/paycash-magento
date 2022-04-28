<?php
 

namespace Paycash\Pay\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Paycash\Pay\Model\Payment as PayCashPayment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

use Magento\Sales\Model\Order;


/**
 * Webhook class  
 */
class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    
    protected $request;
    protected $payment;
    protected $logger;
    protected $invoiceService;
    protected $_transportBuilder;
    protected $_storeManager;
    
    public function __construct(
            Context $context,             
            \Magento\Framework\App\Request\Http $request,
            PayCashPayment $payment,
            \Psr\Log\LoggerInterface $logger_interface,
            \Magento\Sales\Model\Service\InvoiceService $invoiceService,
            Order $order,
            \Paycash\Pay\Mail\Template\TransportBuilder $transportBuilder,
            \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);        
        $this->request = $request;
        $this->payment = $payment;
        $this->logger = $logger_interface;     
        $this->invoiceService = $invoiceService;
        $this->order = $order;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
    }


    public function execute() {        
        $this->logger->debug('#webhook');        
          
        try {
            $body = file_get_contents('php://input');        
            $json = json_decode($body);

            $orderAmount = $json->charge;
            $orderId_test = (int)$json->order_id;
            
            $order = $this->order->loadByIncrementId($orderId_test);
            $orderState = \Magento\Sales\Model\Order::STATE_COMPLETE;
            $order->setState($orderState)->setStatus($orderState);
            $order->addStatusHistoryComment("Pago recibido exitosamente")->setIsCustomerNotified(true);
            $order ->save();

            $info = $order->getPayment()->getAdditionalInformation();
            $coreo = $order->getCustomerEmail();
            
            $order_id = $json->order_id;
            $paid_at = $json->paid_at;
            $charge = $json->charge;
            $payment_method = $json->payment_method;

            $nombre = $order->getCustomerFirstname();
            $apellido = $order->getCustomerLastname();

            $dataforemail = [
                'instruccionesTres' => 'Instrucciones de prueba 3',
                '_paycash_pay_instrucciones' => 'Hemos recibido el pago correspondiente, proceso completado',
                'nombre_usuario' => $nombre,
                'apellido_usuario' => $apellido
            ];

            $this->sendEmail($order, $dataforemail);

            try{
                $file_pointer = BP . '/app/code/Paycash/Pay/TempImgBarCode/'.$orderId_test.'.png';  
                
                if (!unlink($file_pointer)) {
                    $this->setLog($file_pointer.' cannot be deleted due to an error');
                }
                else {
                    $this->setLog($file_pointer. ' has been deleted');
                }
            }
            catch(\Exception $e)
            {
                $this->setLog($e);
                $response = array();
                $response[0] = array(
                    'code' => '1',
                    'message' => $e->getMessage()
                );
                echo json_encode($response);
            }           
              
        } catch (\Exception $e) {
            $this->logger->error('#webhook', array('msg' => $e->getMessage()));  
            $this->setLog($e);
            $this->setLog('#webhook', array('msg' => $e->getMessage())); 

            $response = array();
            $response[0] = array(
                'code' => '1',
                'message' => $e->getMessage()
            );
            echo json_encode($response);
        }
        
        header('HTTP/1.1 200 OK');
        $data = array();
        $response[0] = array(
            'code' => '0'
        );
        echo json_encode($response);

        exit;        
    }       
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function sendEmail($order, $dataforemail = array())
    {    
        try
        {
            $templateId = 'paycash_pdf_processcomplete_template';
            $email = 'demo@demo.com'; 
            $name  = 'demo'; 
            $toEmail = $order->getCustomerEmail();  

            $template_vars = array(
                'title' => 'Proceso pago completado | Orden #'.$order->getIncrementId(),
                'adicional' => $dataforemail,
            );

            $storeId = $this->_storeManager->getStore()->getId();
            $from = array('email' => $email, 'name' => $name);
            
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];

            $transportBuilderObj = $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($template_vars)
            ->setFrom($from)
            ->addTo($toEmail)
            ->getTransport();
            $transportBuilderObj->sendMessage(); 
            return;
        } 
        catch (\Magento\Framework\Exception\MailException $me)
        {            
            $this-> setLog('#MailException', array('msg' => $me->getMessage()));
        }
        catch (\Exception $e)
        {            
            $this-> setLog('#Exception', array('msg' => $e->getMessage()));
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
 