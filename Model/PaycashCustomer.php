<?php

namespace Paycash\Pay\Model;

class paycashCustomer extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface {

    const CACHE_TAG = 'paycash_customers';

    protected $_cacheTag = 'paycash_customers';
    protected $_eventPrefix = 'paycash_customers';
        
    protected function _construct() {
        $this->_init('Paycash\Pay\Model\ResourceModel\PaycashCustomer');        
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues() {
        $values = [];

        return $values;
    }
    
    public function fetchOneBy($field, $value) {        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();                
        $tableName = $connection->getTableName('paycash_customers'); //gives table name with prefix        
        
        $sql = 'Select * FROM '.$tableName.' WHERE '.$field.' = "'.$value.'" limit 1';        
        $result = $connection->fetchAll($sql);
        
        if (count($result)) {
            return json_decode(json_encode($result[0]), false);
        }
        
        return false;
    }


    /**
     * {@inheritDoc}
     */
    public function setPaycashId($paycashId)
    {
        return $this->setData('paycash_id', $paycashId);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaycashId()
    {
        return $this->getData('paycash_id');
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

}
