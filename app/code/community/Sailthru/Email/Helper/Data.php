<?php
/**
 *
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Helper_Data extends Mage_Core_Helper_Abstract {

    // API
    const XML_PATH_ENABLED                                  = 'sailthru/setup/enabled';
    const XML_PATH_ENABLE_LOGGING                           = 'sailthru/setup/enable_logging';

    // Javascript
    const XML_PATH_JS                                       = 'sailthru/js/js_select';
    const XML_PATH_HORIZON_DOMAIN                           = 'sailthru/js/horizon_domain';
    const XML_PATH_CUSTOMER_ID                              = 'sailthru/js/customer_id';
    const JS_SST                                            = 1;
    const JS_HORIZON                                        = 2;

    // User Management
    const XML_PATH_USE_MASTER_LIST                          = 'sailthru_users/management/master_list_enable';
    const XML_PATH_MASTER_LIST                              = 'sailthru_users/management/master_list';
    const XML_PATH_USE_NEWSLETTER_LIST                      = 'sailthru_users/management/enable_newsletter_hook';
    const XML_PATH_NEWSLETTER_LIST                          = 'sailthru_users/management/newsletter_list';

    // Transactional
    const XML_PATH_TRANSACTIONAL_EMAIL_ENABLED              = 'sailthru_transactional/email/enable_transactional_emails';
    const XML_PATH_TRANSACTIONAL_EMAIL_SENDER               = 'sailthru_transactional/email/sender';
    const XML_PATH_TRANSACTIONAL_CUSTOMER_ERROR_ENABLED     = 'sailthru_transactional/email/customer_error_enabled';
    const XML_PATH_TRANSACTIONAL_CUSTOMER_ERROR_MESSAGE     = 'sailthru_transactional/email/customer_error_message';
    const XML_PATH_ABANDONED_CART_ENABLED                   = 'sailthru_transactional/abandoned_cart/enabled';
    const XML_PATH_ABANDONED_CART_TEMPLATE                  = 'sailthru_transactional/abandoned_cart/template';
    const XML_PATH_ABANDONED_CART_DELAY                     = 'sailthru_transactional/abandoned_cart/delay';
    const XML_PATH_ANONYMOUS_CART_ENABLED                   = 'sailthru_transactional/anonymous_cart/enabled';
    const XML_PATH_ANONYMOUS_CART_TEMPLATE                  = 'sailthru_transactional/anonymous_cart/template';
    const XML_PATH_ANONYMOUS_CART_DELAY                     = 'sailthru_transactional/anonymous_cart/delay';

    // Content
    const XML_PATH_PRODUCT_SYNC                             = 'sailthru_content/product_sync/enabled';
    const XML_PATH_PRODUCT_UPDATE_MASTER                    = 'sailthru_content/product_sync/master_products';
    const XML_PATH_PRODUCT_UPDATE_VARIANT                   = 'sailthru_content/product_sync/variant_products';
    const XML_PATH_TAGS_USE_META                            = 'sailthru_content/product_sync/use_meta';
    const XML_PATH_TAGS_USE_CATEGORIES                      = 'sailthru_content/product_sync/use_categories';
    const XML_PATH_TAGS_USE_ATTRIBUTES                      = 'sailthru_content/product_sync/use_attributes';
    const XML_PATH_TAGS_ATTRIBUTE_CODES                     = 'sailthru_content/product_sync/attributes';

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
    public function getMasterList($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MASTER_LIST, $store);
    }

    public function isMasterListEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_USE_MASTER_LIST, $store);
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

    public function isNewsletterListEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_USE_NEWSLETTER_LIST, $store);
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
    public function getSenderEmail($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTIONAL_EMAIL_SENDER, $store);
    }

    public function isCustomerErrorEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTIONAL_CUSTOMER_ERROR_ENABLED);
    }

    public function getCustomerErrorMessage($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TRANSACTIONAL_CUSTOMER_ERROR_MESSAGE);
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
     * Check to see if Sailthru Script Tag is enabled
     *
     * @return bool
     */
    public function isSailthruScriptTagEnabled($store = null)
    {
        return (Mage::getStoreConfig(self::XML_PATH_JS, $store) == self::JS_SST);
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

    /**
     * Check if product sync is on
     * @return bool
     */
    public function isProductSyncEnabled($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_PRODUCT_SYNC, $store));
    }

    /**
     * Check if updating master products is enabled
     * @return bool
     */
    public function updateMasterProducts($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_PRODUCT_UPDATE_MASTER, $store));
    }

    /**
     * Check if updating variant products is enabled
     * @return bool
     */
    public function updateVariantProducts($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_PRODUCT_UPDATE_VARIANT, $store));
    }

    public function tagsUseKeywords($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_TAGS_USE_META, $store));
    }

    public function tagsUseCategories($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_TAGS_USE_CATEGORIES, $store));
    }

    public function tagsUseAttributes($store = null)
    {
        return boolval(Mage::getStoreConfig(self::XML_PATH_TAGS_USE_ATTRIBUTES));
    }

    public function getVariantUrl(Mage_Catalog_Model_Product $product, $parentId)
    {
        if ($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            $parent = Mage::getModel('catalog/product')->load($parentId);
            return str_replace(" ", "_", "{$parent->getProductUrl(false)}#{$product->getSku()}");
        }
        return $product->getUrl();
    }

    /**
     * Get all applicable attributes for product tags
     * @param null $store
     * @return array
     */
    public function getUsableAttributeCodes($store = null)
    {
        return explode(",", Mage::getStoreConfig(self::XML_PATH_TAGS_ATTRIBUTE_CODES, $store));
    }

    public function getTags(Mage_Catalog_Model_Product $product)
    {
        $tags = '';
        if ($this->tagsUseKeywords()) {
            $keywords = htmlspecialchars($product->getMetaKeyword());
            $tags .= "{$keywords},";
        }
        if ($this->tagsUseCategories()) {
            $categories = $this->getCategories($product);
            $tags .= implode(",", $categories);
        }
        if ($this->tagsUseAttributes()) {
            try {
                $attribute_str = '';
                $attributes = $this->getProductAttributeValues($product);
                foreach ($attributes as $key => $value) {
                    if (!is_numeric($value)) {
                        $attribute_str .= (($value == "Yes" or $value == "Enabled") ? $key : $value) . ",";
                    }
                }
                $tags .= $attribute_str;
            } catch (Exception $e) {
                Mage::log("Error building product tags:", null, "sailthru.log");
                Mage::log($e->getMessage(), null, "sailthru.log");
            }
        }

        return $tags;
    }

    public function getProductAttributeValues($product)
    {
        $values = [];

        $usableAttributeCodes = $this->getUsableAttributeCodes();
        $productAttributes = $product->getAttributes();
        foreach ($productAttributes as $key => $attribute) {
            if (in_array($key, $usableAttributeCodes)) {
                $label = $attribute->getFrontendLabel();
                $value = $attribute->getFrontend()->getValue($product);
                if ($value and $label and $value != "No" and $value != " ") {
                    $values[$label] = $value;
                }
            }
        }
        return $values;
    }
    public function getCategories($product)
    {
        $collection = $product->getCategoryCollection();
        $items = $collection->addAttributeToSelect('name')->getItems();
        $categories = [];
        foreach ($items as $item) {
            $categories[] = $item->getName();
        }
        return $categories;
    }

    public function getPrice($product)
    {
        $current_price = $product->getFinalPrice();
        $final_price = Mage::helper('sailthruemail')->formatAmount($current_price);
        return $final_price;
    }

    /**
     * Get vars for address information, optionally adding a prefix.
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param string                               $prefix
     * @param bool                                 $useFullAddress
     * @return array
     */
    public function getAddressVars(Mage_Customer_Model_Address_Abstract $address, $prefix=null, $useFullAddress=false){
        $vars = [
            "city"           => $address->getCity(),
            "state"          => $address->getRegion(),
            "state_code"     => $address->getRegionCode(),
            "country"        => Mage::app()->getLocale()->getCountryTranslation($address->getCountry()),
            "country_code"   => $address->getCountry(),
            "postal_code"    => $address->getPostcode(),
        ];

        if ($useFullAddress) {
            $vars += [
                "name"      => $address->getName(),
                "company"   => $address->getCompany(),
                "telephone" => $address->getTelephone(),
                "street1"   => $address->getStreet1(),
                "street2"   => $address->getStreet2(),
            ];
        }

        if (!is_null($prefix)){
            $varsCopy = [];
            foreach ($vars as $key => $value) {
                $varsCopy["{$prefix}{$key}"] = $value;
            }
            return $varsCopy;
        }
        return $vars;
    }

}