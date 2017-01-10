<?php
/**
 * Abstract model for utility functions
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */

abstract class Sailthru_Email_Model_Abstract extends Mage_Core_Model_Abstract
{
    /**
     *
     * Current Store Id
     * @var string
     */
    protected $_storeId;

    protected $_isEnabled = false;

    protected $_session = null;

    protected $_customer = null;

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

    /**
     * Returns an MD5 hash of the signature string for an API call.
     *
     * This hash should be passed as the 'sig' value with any API request.
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    public static function getSignatureHash($params, $secret) {
        return md5(self::getSignatureString($params, $secret));
    }


    /**
     * Returns the unhashed signature string (secret + sorted list of param values) for an API call.
     *
     * Note that the SORT_STRING option sorts case-insensitive.
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    public static function getSignatureString($params, $secret) {
        $values = array();
        self::extractParamValues($params, $values);
        sort($values, SORT_STRING);
        $string = $secret . implode('', $values);
        return $string;
    }


    /**
     * Extracts the values of a set of parameters, recursing into nested assoc arrays.
     *
     * @param array $params
     * @param array $values
     */
    public static function extractParamValues($params, &$values) {
        foreach ($params as $k => $v) {
            if (is_array($v) || is_object($v)) {
                self::extractParamValues($v, $values);
            } else {
                if (is_bool($v))  {
                    //if a value is set as false, invalid hash will generate
                    //https://github.com/sailthru/sailthru-php5-client/issues/4
                    $v = intval($v);
                }
                $values[] = $v;
            }
        }
    }

    public function setCookie($response) {
        if (array_key_exists('ok',$response) && array_key_exists('keys',$response)) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);
            $domain = $domain_parts[sizeof($domain_parts)-2] . '.' . $domain_parts[sizeof($domain_parts)-1];
            Mage::getModel('core/cookie')->set('sailthru_hid',$response['keys']['cookie'],null,null,$domain,null,false);
            return true;
        } else {
            return false;
        }
    }
    public function log($data) {
        if (Mage::helper('sailthruemail')->isLoggingEnabled($this->_storeId)) {
            Mage::log($data,null,'sailthru.log');
        }
    }
}
