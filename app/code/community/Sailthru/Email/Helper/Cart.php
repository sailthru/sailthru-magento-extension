<?php

class Sailthru_Email_Helper_Cart extends Mage_Core_Helper_Abstract {

    const XML_PATH_ABANDONED_CART_ENABLED                   = 'sailthru_transactional/abandoned_cart/enabled';
    const XML_PATH_ANONYMOUS_CART_ENABLED                   = 'sailthru_transactional/anonymous_cart/enabled';
    const XML_PATH_ABANDONED_CART_USE_EMAIL                 = 'sailthru_transactional/abandoned_cart/use';
    const XML_PATH_ANONYMOUS_CART_USE_EMAIL                 = 'sailthru_transactional/anonymous_cart/use';
    const XML_PATH_ABANDONED_CART_DELAY                     = 'sailthru_transactional/abandoned_cart/delay';
    const XML_PATH_ANONYMOUS_CART_DELAY                     = 'sailthru_transactional/anonymous_cart/reminder_time';
    const XML_PATH_ABANDONED_CART_TEMPLATE                  = 'sailthru_transactional/abandoned_cart/template';
    const XML_PATH_ANONYMOUS_CART_TEMPLATE                  = 'sailthru_transactional/anonymous_cart/template';

    public function isAbandonedCartEnabled($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_ENABLED, $store));
    }

    public function isAnonymousCartEnabled($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_ENABLED, $store));
    }

    public function isAbandonedCartEmailEnabled($store = null)
    {
        return boolval(Mage::getStoreconfig(self::XML_PATH_ABANDONED_CART_USE_EMAIL, $store));
    }

    public function isAnonymousCartEmailEnabled($store = null)
    {
        return boolval(Mage::getStoreconfig(self::XML_PATH_ANONYMOUS_CART_USE_EMAIL, $store));
    }

    public function getAbandonedCartDelayTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_DELAY, $store);
    }

    public function getAnonymousCartDelayTime($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_DELAY, $store);
    }

    public function getAbandonedCartTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ABANDONED_CART_TEMPLATE, $store);
    }

    public function getAnonymousCartTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ANONYMOUS_CART_TEMPLATE, $store);
    }

}