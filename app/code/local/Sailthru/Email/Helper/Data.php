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
    const XML_PATH_API_KEY                                  = 'sailthru/api/key';
    const XML_PATH_API_SECRET                               = 'sailthru/api/secret';
    const XML_PATH_LOG_PATH                                 = 'sailthru/api/log_path';
    const XML_PATH_DEFAULT_EMAIL_LIST                       = 'sailthru/email/default_list';
    const XML_PATH_DEFAULT_NEWSLETTER_LIST                  = 'sailthru/email/newsletter_list';
    const XML_PATH_SENDER_EMAIL                             = 'sailthru/email/sender_email';
    const XML_PATH_HORIZON_ENABLED                          = 'sailthru/horizon/active';
    const XML_PATH_HORIZON_DOMAIN                           = 'sailthru/horizon/horizon_domain';
    const XML_PATH_CONCIERGE_ENABLED                        = 'sailthru/horizon/concierge_enabled';
    const XML_PATH_ABANDONED_CART                           = 'sailthru/email/abandoned_cart';
    const XML_PATH_ABANDONED_CART_SENDER_EMAIL              = 'sailthru/email/abandoned_cart_sender_email';
    const XML_PATH_ABANDONED_CART_SENDER_NAME               = 'sailthru/email/abandoned_cart_sender_name';
    const XML_PATH_ABANDONED_CART_TEMPLATE                  = 'sailthru/email/abandoned_cart_template';
    const XML_PATH_ABANDONED_CART_SUBJECT                   = 'sailthru/email/abandoned_cart_subject';
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

    public function getKey($store = null)
    {
        $apiKey = Mage::getStoreConfig(self::XML_PATH_API_KEY, $store);
        return $apiKey;
    }

    public function getSecret($store = null)
    {
        $apiSecret = Mage::getStoreConfig(self::XML_PATH_API_SECRET, $store);
        return $apiSecret;
    }

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

    public function newSailthruClient($store = null) {
        include_once("sailthru_api/Sailthru_Client_Exception.php");
        include_once("sailthru_api/Sailthru_Client.php");
        include_once("sailthru_api/Sailthru_Util.php");
        $sailthru = new Sailthru_Client(self::getKey($store), self::getSecret($store));
        $sailthru->setLogPath(self::getLogPath($store));
        return $sailthru;
    }

    public function isTransactionalEmailEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTION_EMAIL_ENABLED, $store);
    }

    public function sendAbandonedCartEmails($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART, $store);
    }

    public function getAbandonedCartSenderEmail($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_SENDER_EMAIL, $store);
    }

    public function getAbandonedCartSenderName($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_SENDER_NAME, $store);
    }

    public function getAbandonedCartTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_TEMPLATE, $store);
    }

    public function getAbandonedCartSubject($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_SUBJECT, $store);
    }
    
    public function getSenderEmail()
    {
        return Mage::getStoreConfig(self::XML_PATH_SENDER_EMAIL);
    }

    public function getAbandonedCartReminderTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_REMINDER_TIME, $store);
    }
    
    public function getDefaultList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_EMAIL_LIST, $store);
    }
    
    public function getNewsletterList($store = null) 
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_NEWSLETTER_LIST, $store);
    }


    public function createAbandonedCartHtmlContent()
    {
        //It's important to note that the code below only works if routed through Sailthru.
        $content_html = '{*Sailthru zephyr code is used for full functionality*}
                                    <div id="main">
                                        <table width="700">
                                            <tr>
                                                <td>
                                                    <h2><p>Hello {profile.vars.name}</p></h2>
                                                    <p>Did you forget the following items in your cart?</p>

                                                    <table>
                                                        <thead>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <div><span style="display:block;text-align:center;color:white;font-size:13px;font-weight:bold;padding:15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{profile.purchase_incomplete.items[0].vars.checkout_url}">Re-Order Now!</a></span></div>
                                                                </td>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                        {sum = 0}
                                                        {foreach profile.purchase_incomplete.items as i}
                                                            <table width="650" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px 0;background:#fff;border:1px solid #e5e5e5">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="padding:20px"><a href="{i.url}"><img width="180" height="135" border="0" alt="{i.title}" src="{i.vars.image_url}"></a></td>
                                                                        <td width="420" valign="top" style="padding:20px 10px 20px 0">
                                                                            <div style="padding:5px 0;color:#333;font-size:18px;font-weight:bold;line-height:21px">{i.title}</div>
                                                                            <div style="padding:0 0 5px 0;color:#999;line-height:21px;margin:0px">{i.vars.currency}{i.price/100}</div>
                                                                            <div style="color:#999;font-weight:bold;line-height:21px;margin:0px">{i.description}</div>
                                                                            <div><span style="display:block;text-align:center;width:120px;border-left:1px solid #b43e2e;border-right:1px solid #b43e2e;color:white;font-size:13px;font-weight:bold;padding:0 15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{i.url}">Buy Now</a></span></div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        {/foreach}
                                                        <tr>
                                                            <td align="left" valign="top" style="padding:3px 9px" colspan="2"></td>
                                                            <td align="right" valign="top" style="padding:3px 9px"></td>
                                                        </tr>

                                                        </tbody>

                                                        <tfoot>

                                                        </tfoot>
                                                    </table>

                                                    <p><small>If you believe this has been sent to you in error, please safely <a href="{optout_confirm_url}">unsubscribe</a>.</small></p>
                                                    {beacon}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>'; //include css or tables here to style e-mail.
                            //It's important that the "from_email" is verified, otherwise the code below will not work.
        return $content_html;
    }

    public function debug($object)
    {
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }

}
