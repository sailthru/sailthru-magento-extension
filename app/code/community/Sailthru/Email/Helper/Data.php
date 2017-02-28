<?php
/**
 *
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Helper_Data extends Mage_Core_Helper_Abstract {

    // API
    const XML_PATH_ENABLED                                  = 'sailthru/api/enabled';
    const XML_PATH_ENABLE_LOGGING                           = 'sailthru/api/enable_logging';
    
    // User Management
    const XML_PATH_DEFAULT_EMAIL_LIST                       = 'sailthru_users/management/default_list';
    const XML_PATH_NEWSLETTER_LIST                          = 'sailthru_users/management/newsletter_list';
    
    // Transactionals
    const XML_PATH_TRANSACTIONAL_EMAIL_ENABLED              = 'sailthru_transactional/email/enable_transactional_emails';
    const XML_PATH_TRANSACTIONAL_EMAIL_SENDER               = 'sailthru_transactional/email/sender';
    const XML_PATH_ABANDONED_CART_ENABLED                   = 'sailthru_transactional/abandoned_cart/enabled';
    const XML_PATH_ABANDONED_CART_TEMPLATE                  = 'sailthru_transactional/abandoned_cart/template';
    const XML_PATH_ABANDONED_CART_DELAY                     = 'sailthru_transactional/abandoned_cart/delay';
    const XML_PATH_ANONYMOUS_CART_ENABLED                   = 'sailthru_transactional/anonymous_cart/enabled';
    const XML_PATH_ANONYMOUS_CART_TEMPLATE                  = 'sailthru_transactional/anonymous_cart/template';
    const XML_PATH_ANONYMOUS_CART_DELAY                     = 'sailthru_transactional/anonymous_cart/delay';

    // Other
    const XML_PATH_JS                                       = 'sailthru/js/js_select';
    const XML_PATH_HORIZON_DOMAIN                           = 'sailthru/js/horizon_domain';
    const XML_PATH_CUSTOMER_ID                              = 'sailthru/js/customer_id';
    const JS_SPM                                            = 1;
    const JS_HORIZON                                        = 2;



    /**
     * Check to see if Sailthru plugin is enabled
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return (boolean) Mage::getStoreConfig(self::XML_PATH_ENABLED, $store);
    }

    public function isLoggingEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLE_LOGGING, $store);
    }

    /**
     * Get list where all users will be added
     * @param type $store
     * @return string
     */
    public function getDefaultList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_EMAIL_LIST, $store);
    }

    /**
     * Get Newsletter list
     * @param type $store
     * @return string
     */
    public function getNewsletterList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_NEWSLETTER_LIST, $store);
    }

    /**
     * Get transactional enabled flag
     * @param store
     * @return boolean int
     */
    public function isTransactionalEmailEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTIONAL_EMAIL_ENABLED, $store);
    }
    /**
     * Get sender for transactional email
     * @param store
     * @return string
     */
    public function getSenderEmail()
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTIONAL_EMAIL_SENDER, $store);
    }

    public function isAbandonedCartEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_ENABLED, $store);
    }

    public function getAbandonedCartDelayTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_DELAY, $store);
    }

    public function getAbandonedCartTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_TEMPLATE, $store);
    }

    public function isAnonymousCartEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_ENABLED, $store);
    }

    public function getAnonymousCartDelayTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_DELAY, $store);
    }

    public function getAnonymousCartTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_TEMPLATE, $store);
    }

    /**
     * Check to see if Horizon is enabled
     *
     * @return bool
     */
    public function isHorizonEnabled($store = null)
    {
        return (Mage::getStoreConfig(self::XML_PATH_JS, $store) == self::JS_HORIZON);
    }

    /**
     * Check to see if PersonalizeJS is enabled
     *
     * @return bool
     */
    public function isPersonalizeJsEnabled($store = null)
    {
        return (Mage::getStoreConfig(self::XML_PATH_JS, $store) == self::JS_SPM);
    }

    /**
     * Get Horizon domain
     * @param type $store
     * @return string
     */
    public function getHorizonDomain($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_HORIZON_DOMAIN, $store);
    }

    /**
     * Get Horizon domain
     * @param type $store
     * @return string
     */
    public function getCustomerId($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMER_ID, $store);
    }

    public function formatAmount($amount = null)
    {
        if (is_numeric($amount)) {
            return intval($amount*100);
        }

        return 0;

    }

    public function getPrice($product){
        $current_price = $product->getFinalPrice();
        $final_price = Mage::helper('sailthruemail')->formatAmount($current_price);
        return $final_price;
    }

    public function debug($object)
    {
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }
}