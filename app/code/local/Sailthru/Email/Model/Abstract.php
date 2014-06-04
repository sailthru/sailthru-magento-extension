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

    protected $_customer = null;

    protected $_customerEmail = null;

    public function __construct()
    {
        $this->_storeId = Mage::app()->getStore()->getStoreId();
        $this->_logPath = Mage::helper('sailthruemail')->getLogPath($this->_storeId);

        if(Mage::helper('sailthruemail')->isEnabled($this->_storeId)) {
            $this->_isEnabled = true;
            if ($this->_customer = Mage::getSingleton('customer/session')->getCustomer()) {
                $this->_customerEmail = $this->_customer->getEmail();
            }
        }
    }

    protected function _debug($object) {
        return Mage::helper('sailthruemail')->debug($object);
    }

    protected function _getApiKey()
    {
        return Mage::getStoreConfig('sailthru/api/key', $this->_storeId);
    }

    protected function _getApiSecret()
    {
        return Mage::getStoreConfig('sailthru/api/secret', $this->_storeId);
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
}