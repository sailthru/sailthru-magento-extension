<?php

/**
 * Modified version of the Sailthru PHP5 Client v1.0.9
 */

$dir = Mage::getConfig()->getModuleDir('', 'Sailthru_Email');
require_once $dir."/lib/Sailthru_Client.php";
require_once $dir."/lib/Sailthru_Client_Exception.php";
require_once $dir."/lib/Sailthru_Util.php";

class Sailthru_Email_Model_Client extends Sailthru_Client {

    /**
     * Event type (add,delete,update) for logging
     * @var string
     */
    protected $_eventType = null;

    /**
     * Current Store Id
     * @var string
     */
    protected $_storeId;

    /** @var bool  */
    protected $_isEnabled = false;

    /** @var Mage_Customer_Model_Session  */
    protected $_session = null;

    /** @var Mage_Core_Model_Abstract */
    protected $_customer = null;

    /** @var string */
    protected $_email = null;

    public function __construct() {
        $this->_storeId = Mage::app()->getStore()->getStoreId();

        if(Mage::helper('sailthruemail')->isEnabled($this->_storeId)) {
            $this->_isEnabled = true;
            $this->_session = Mage::getSingleton('customer/session');
            if ($this->_session->isLoggedIn()) {
                $this->_customer = Mage::getModel('customer/customer')->load($this->_session->getId());
                $this->_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            }
        }

        $apiKey = Mage::getStoreConfig('sailthru/api/key', $this->_storeId);
        $apiSecret = Mage::getStoreConfig('sailthru/api/secret', $this->_storeId);
        $apiUri =  Mage::getStoreConfig('sailthru/api/uri', $this->_storeId);
        parent::__construct($apiKey, $apiSecret, $apiUri);
    }

    /**
     * HTTP request wrapper for debugging
     * @inheritdoc
     */
    protected function httpRequest($action, $data, $method = 'POST', $options = [ ])
    {
        $logAction = "{$method} /{$action}";

        $this->log([
            'action'            => $logAction,
            'event_type'        => $this->_eventType,
            'store_id'           => $this->_storeId,
            'http_request_type' => $this->http_request_type,
            'request'           => $data['json'],
        ]);
        try {
            $response = parent::httpRequest($action, $data, $method, $options);
        } catch (Sailthru_Client_Exception $e) {
            $this->log([
                'action'        => $logAction,
                'error'         => $e->getCode(),
                'error message' => $e->getMessage(),
            ]);
            throw $e;
        }
        $this->log([
            'response_from' => "{$method} /{$action}",
            'event_type'    => $this->_eventType,
            'store_id'      => $this->_storeId,
            'response'      => $response,
        ]);
        return $response;
    }

    /**
     * Get sailthru_bid cookie value
     *
     * @return string
     */
    public function getMessageId() {
        $cookie_vals = Mage::getModel('core/cookie')->get();
        return isset($cookie_vals['sailthru_bid']) ? $cookie_vals['sailthru_bid'] : null;
    }

    public function log($data) {
        if (Mage::helper('sailthruemail')->isLoggingEnabled($this->_storeId)) {
            Mage::log($data,null,'sailthru.log');
        }
    }

    // Magento-friendly cookie-setter
    public function setCookie($response) {
        if (array_key_exists('ok',$response) && array_key_exists('keys',$response)) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);
            $domain = $domain_parts[sizeof($domain_parts)-2] . '.' . $domain_parts[sizeof($domain_parts)-1];
            Mage::getModel('core/cookie')->set('sailthru_hid',$response['keys']['cookie'],31622400,null,$domain,null,false);
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * Set Horizon cookie
     *
     * @param string $email horizon user email
     * @param string $domain
     * @param integer $duration
     * @param boolean $secure
     * @return boolean
     */
    public function setHorizonCookie($email, $domain = null, $duration = null, $secure = false) {
        $data = $this->getUserByKey($email, 'email', [ 'keys' => 1 ]);
        if (!isset($data['keys']['cookie'])) {
            return false;
        }
        if (!$domain) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);
            $domain = $domain_parts[sizeof($domain_parts) - 2] . '.' . $domain_parts[sizeof($domain_parts) - 1];
        }
        if ($duration === null) {
            $expire = time() + 31556926;
        } else if ($duration) {
            $expire = time() + $duration;
        } else {
            $expire = 0;
        }
        // Magento-friendly cookie-setter
        return Mage::getModel('core/cookie')->set('sailthru_hid', $data['keys']['cookie'], $expire, '/', $domain, $secure);

    }
}