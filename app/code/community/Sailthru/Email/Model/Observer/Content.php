<?php
/**
 * Content API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Content extends Sailthru_Email_Model_Abstract
{

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled) {
            try{
                $product = $observer->getEvent()->getProduct();
                $response = Mage::getModel('sailthruemail/client_content')->deleteProduct($product);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Push product to Sailthru using Content API
     *
     * @param Mage_Sales_Model_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function saveProduct(Mage_Sales_Model_Observer $observer)
    {
        $eventProduct = $observer->getEvent()->getProduct();
        $productId = $eventProduct->getId();
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $stores = $this->getScopedStores($eventProduct);
        foreach ($stores as $storeId) {
            if (Mage::helper('sailthruemail')->isEnabled($storeId) and Mage::helper('sailthruemail')->isProductSyncEnabled($storeId)) {
                $emulateData = $appEmulation->startEnvironmentEmulation($storeId);
                $product = Mage::getModel('catalog/product')->load($productId); // get the full product.
                $status = $product->getStatus();
                $sailthruContent = Mage::getModel('sailthruemail/client_content');
                try {
                    if ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                        $response = $sailthruContent->saveProduct($product);
                    } elseif ($status == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                        $response = $sailthruContent->deleteProduct($product);
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
                $appEmulation->stopEnvironmentEmulation($emulateData);
            }
        }
        return $observer;
    }

    private function getScopedStores(Mage_Catalog_Model_Product $product)
    {
        $storeId = Mage::app()->getRequest()->getParam('store');
        if ($storeId) {
            return [$storeId];
        }
        $storeIds = $product->getStoreIds();
        $websiteId = Mage::app()->getRequest()->getParam('website');
        if ($websiteId) {
            $websiteStoreIds = Mage::getModel('core/website')->load($websiteId)->getStoreIds();
            return array_intersect($storeIds, $websiteStoreIds);
        }
        return $storeIds;
    }

}