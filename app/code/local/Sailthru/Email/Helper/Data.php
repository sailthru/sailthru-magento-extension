<?php
/**
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Helper_Data extends Mage_Core_Helper_Abstract {
    /*
        * Config paths to use
        */
    const XML_PATH_ENABLED                                  = 'sailthru/api/enabled';
    const XML_PATH_LOG_PATH                                 = 'sailthru/api/log_path';
    const XML_PATH_DEFAULT_EMAIL_LIST                       = 'sailthru/email/default_list';
    const XML_PATH_DEFAULT_NEWSLETTER_LIST                  = 'sailthru/email/newsletter_list';
    const XML_PATH_SENDER_EMAIL                             = 'sailthru/email/sender_email';
    const XML_PATH_HORIZON_ENABLED                          = 'sailthru/horizon/active';
    const XML_PATH_HORIZON_DOMAIN                           = 'sailthru/horizon/horizon_domain';
    const XML_PATH_CONCIERGE_ENABLED                        = 'sailthru/horizon/concierge_enabled';
    const XML_PATH_REMINDER_TIME                            = 'sailthru/email/reminder_time';
    const XML_PATH_TRANSACTION_EMAIL_ENABLED                = 'sailthru/email/enable_transactional_emails';
    const XML_PATH_IMPORT_SUBSCRIBERS                       = 'sailthru/subscribers/import_subscribers';

    /**
     * Check to see if Sailthru plugin is enabled
     *
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED, $store);
    }
    /**
     *
     * @param type $store
     * @return type
     */

    public function getLogPath($store = null)
    {
        $log_path = Mage::getStoreConfig(self::XML_PATH_LOG_PATH, $store);
        if (empty($log_path)) {
            return null;
        } else {
            return $log_path;
        }
    }

    public function getHorizonDomain($store = null)
    {
        $horizonDomain = Mage::getStoreConfig(self::XML_PATH_HORIZON_DOMAIN, $store);
        return $horizonDomain;
    }

    public function getMasterList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_EMAIL_LIST, $store);
    }

    public function isHorizonEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_HORIZON_ENABLED, $store);
    }

    public function isConciergeEnabled($store = null)
    {
        $conciergeEnabled = Mage::getStoreConfig(self::XML_PATH_CONCIERGE_ENABLED, $store);
        return $conciergeEnabled;
    }

    public function importSubscribers($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_IMPORT_SUBSCRIBERS, $store);
    }

    public function isTransactionalEmailEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTION_EMAIL_ENABLED, $store);
    }

    public function getSenderEmail()
    {
        return Mage::getStoreConfig(self::XML_PATH_SENDER_EMAIL);
    }

    public function getDefaultList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_EMAIL_LIST, $store);
    }

    public function getNewsletterList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_NEWSLETTER_LIST, $store);
    }

    public function getReminderTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_REMINDER_TIME, $store);
    }


    public function debug($object)
    {
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }
}