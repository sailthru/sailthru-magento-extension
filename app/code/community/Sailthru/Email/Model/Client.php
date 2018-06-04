<?php

/**
 * Modified version of the Sailthru PHP5 Client v1.0.9
 */

$dir = Mage::getConfig()->getModuleDir('', 'Sailthru_Email');
require_once $dir."/lib/Sailthru_Client.php";
require_once $dir."/lib/Sailthru_Client_Exception.php";
require_once $dir."/lib/Sailthru_Util.php";

class Sailthru_Email_Model_Client extends Sailthru_Client
{

    /**
     * Event type (add,delete,update) for logging
     * @var string
     */
    protected $_eventType = null;

    /**
     * Current Store Id. Used for logging.
     * @var string
     */
    protected $_storeId;

    /** @var Mage_Customer_Model_Session  */
    protected $_session = null;

    /** @var Mage_Core_Model_Abstract */
    protected $_customer = null;

    /** @var string */
    protected $_email = null;

    public function __construct() 
    {

        $this->_storeId = Mage::app()->getStore()->getStoreId();
        $this->_session = Mage::getSingleton('customer/session');
        if ($this->_session->isLoggedIn()) {
            $this->_customer = Mage::getModel('customer/customer')->load($this->_session->getId());
            $this->_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }

        $apiKey = Mage::getStoreConfig('sailthru/api/key', $this->_storeId);
        $apiSecret = Mage::getStoreConfig('sailthru/api/secret', $this->_storeId);
        $apiUri = Mage::getStoreConfig('sailthru/api/uri', $this->_storeId);
        parent::__construct($apiKey, $apiSecret, $apiUri);
    }

    protected function prepareJsonPayload(array $data, array $binary_data = array())
    {
        $version = (string) Mage::getConfig()->getNode('modules/Sailthru_Email/version');
        $data['integration'] = "Magento 1 - $version";
        return parent::prepareJsonPayload($data, $binary_data);
    }

    /**
     * HTTP request wrapper for debugging
     * @inheritdoc
     */
    protected function httpRequest($action, $data, $method = 'POST', $options = array( ))
    {
        $logAction = "{$method} /{$action}";

        $this->log(
            array(
            'action'            => $logAction,
            'event_type'        => $this->_eventType,
            'store_id'           => $this->_storeId,
            'http_request_type' => $this->http_request_type,
            'request'           => $data['json'],
            )
        );
        try {
            $response = parent::httpRequest($action, $data, $method, $options);
        } catch (Sailthru_Client_Exception $e) {
            $this->log(array(
                'action'        => $logAction,
                'error'         => $e->getCode(),
                'error message' => $e->getMessage(),
                ), Zend_Log::ERR);
            throw $e;
        }

        $this->log(
            array(
            'response_from' => "{$method} /{$action}",
            'event_type'    => $this->_eventType,
            'store_id'      => $this->_storeId,
            'response'      => $response,
            )
        );
        return $response;
    }

    /**
     * Get sailthru_bid cookie value
     *
     * @return string
     */
    public function getMessageId() 
    {
        $cookie_vals = Mage::getModel('core/cookie')->get();
        return isset($cookie_vals['sailthru_bid']) ? $cookie_vals['sailthru_bid'] : null;
    }

    public function log($data, $level=null)
    {
        if (Mage::helper('sailthruemail')->isLoggingEnabled($this->_storeId)) {
            Mage::log($data, $level, 'sailthru.log');
        }
    }

    public function logException(Exception $e)
    {
        Mage::logException($e);
        $this->log("Error: " . get_class($e) . ": {$e->getMessage()}. See exception.log for more details", Zend_Log::ERR);
    }

    // Magento-friendly cookie-setter. Kept for backwards-compatibility with old plugin. TODO: refactor
    public function setCookie($response) 
    {
        if (array_key_exists('ok', $response) && array_key_exists('keys', $response)) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);
            $domain = $domain_parts[sizeof($domain_parts)-2] . '.' . $domain_parts[sizeof($domain_parts)-1];
            Mage::getModel('core/cookie')->set('sailthru_hid', $response['keys']['cookie'], 31622400, null, $domain, null, false);
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
    public function setHorizonCookie($email, $domain = null, $duration = null, $secure = false) 
    {
        $data = $this->getUserByKey($email, 'email', array( 'keys' => 1 ));
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

    public function testKeys($apiKey=null, $apiSecret=null, $apiUri=null)
    {
        if ($apiKey and $apiSecret) {
            parent::__construct($apiKey, $apiSecret, $apiUri);
        }

        $this->apiGet('settings', array());
    }
}