<?php
 

namespace Paycash\Pay\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Paycash\Pay\Model\Payment as PayCashPayment;
//use Paycash\Pay\Model\Payment as OpenpayPayment;

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
    protected $orderMag;
    
    public function __construct(
            Context $context,             
            \Magento\Framework\App\Request\Http $request, 
            //OpenpayPayment $payment, 
            PayCashPayment $payment,
            \Psr\Log\LoggerInterface $logger_interface,
            \Magento\Sales\Model\Service\InvoiceService $invoiceService,
            Order $order
    ) {
        parent::__construct($context);        
        $this->request = $request;
        $this->payment = $payment;
        $this->logger = $logger_interface;     
        $this->invoiceService = $invoiceService;
        $this->order = $order;
    }


    public function execute() {        
        $this->logger->debug('#webhook');        
          
        try {
            $body = file_get_contents('php://input');        
            $json = json_decode($body); 
            
            $this-> setLog("id cliente de la transaccion con body");
            $this-> setLog($body);   

            $this-> setLog($json->order_id);  
            $this-> setLog($json->paid_at);  
            $this-> setLog($json->charge);  
            $this-> setLog($json->payment_method);  

            $this-> setLog("impresion de request data completa...");

            $orderAmount = $json->charge;
            $orderId_test = (int)$json->order_id;//181
            $this-> setLog($orderId_test);
            
            $order = $this->order->loadByIncrementId($orderId_test);
            $orderState = \Magento\Sales\Model\Order::STATE_COMPLETE;
            $order->setState($orderState)->setStatus($orderState);
            $order->setTotalPaid($orderAmount); 
            $order->addStatusHistoryComment("Pago recibido exitosamente")->setIsCustomerNotified(true);
            $order ->save();

            $this->setLog("status & state Orden actualizado...");
            $info = $order->getPayment()->getAdditionalInformation();
            $coreo = $order->getCustomerEmail();
            $this->setLog($info);
            $this->setLog($coreo);
            
            $order_id = $json->order_id;
            $paid_at = $json->paid_at;
            $charge = $json->charge;
            $payment_method = $json->payment_method;

            $this->setLog($order_id);
            $this->setLog($paid_at);
            $this->setLog($charge);
            $this->setLog($payment_method);
            
            $this-> setLog("validando...");

            //$paycash = $this->payment->getOpenpayInstance();
            //$this-> setLog('Despues de getOpenPayInstance');
            
            /*if(isset($json->transaction->customer_id)){
                $customer = $paycash->customers->get($json->transaction->customer_id);
                $charge = $customer->charges->get($json->transaction->id);
            }else{
                $charge = $paycash->charges->get($json->transaction->id);
            }*/

            //$this->logger->debug('#webhook', array('trx_id' => $json->transaction->id, 'status' => $charge->status));        

            if (isset($json->type) && ($json->transaction->method == 'store' || $json->transaction->method == 'bank_account')) 
            {
                $order = $this->_objectManager->create('Magento\Sales\Model\Order');            
                $order->loadByAttribute('ext_order_id', $charge->id);

                if($json->type == 'charge.succeeded' && $charge->status == 'completed' ){
                    $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $order->setState($status)->setStatus($status);
                    $order->setTotalPaid($charge->amount);  
                    $order->addStatusHistoryComment("Pago recibido exitosamente")->setIsCustomerNotified(true);            
                    $order->save();
                    
                    $invoice = $this->invoiceService->prepareInvoice($order);        
                    $invoice->setTransactionId($charge->id);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->save();
                }else if($json->type == 'transaction.expired' && $charge->status == 'cancelled'){
                    $status = \Magento\Sales\Model\Order::STATE_CANCELED;
                    $order->setState($status)->setStatus($status);
                    $order->addStatusHistoryComment("Pago vencido")->setIsCustomerNotified(true);            
                    $order->save();
                }
            }                    
        } catch (\Exception $e) {
            $this->logger->error('#webhook', array('msg' => $e->getMessage()));  
            $this->setLog($e);
            $this->setLog('#webhook', array('msg' => $e->getMessage())); 
        }
        
        header('HTTP/1.1 200 OK');
        exit;        
    }       
    
    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     * @link https://magento.stackexchange.com/questions/253414/magento-2-3-upgrade-breaks-http-post-requests-to-custom-module-endpoint
     *
     * @return InvalidRequestException|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
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

    public function setLog($log)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/paycash.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($log);
    }

}
 