<?php
/**
 * Content API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Content
{

    const FAILURE_MESSAGE = "The product was not properly sync'd with Sailthru";

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        try{
            $product = $observer->getEvent()->getProduct();
            $response = Mage::getModel('sailthruemail/client_content')->deleteProduct($product);
        } catch (Sailthru_Client_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer
     */
    public function saveProduct(Varien_Event_Observer $observer)
    {
        $inBackend = (Mage::app()->getStore()->getStoreId() == 0);
        $eventProduct = $observer->getEvent()->getProduct();
        $productId = $eventProduct->getId();
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $stores = $this->getScopedStores($eventProduct);

        $savedStores = array();

        foreach ($stores as $storeId) {
            if (Mage::helper('sailthruemail')->isEnabled($storeId) and Mage::helper('sailthruemail')->isProductSyncEnabled($storeId)) {
                $emulateData = $appEmulation->startEnvironmentEmulation($storeId);
                $product = Mage::getModel('catalog/product')->load($productId); // get the full product.
                $status = $product->getStatus();
                $sailthruContent = Mage::getModel('sailthruemail/client_content');
                try {
                    $saved = false;
                    if ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                        $saved = $sailthruContent->saveProduct($product);
                    } elseif ($status == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                        $saved = $sailthruContent->deleteProduct($product);
                    }

                    if ($saved) {
                        $savedStores[] = Mage::app()->getStore()->getName();
                    }
                } catch (Sailthru_Client_Exception $e) {
                    if ($inBackend) {
                        $this->processContentError($e);
                    }
                }

                $appEmulation->stopEnvironmentEmulation($emulateData);
            }
        }

        if (count($savedStores)) {
            $storesString = implode(", ", $savedStores);
            Mage::getSingleton('core/session')->addNotice("Sync'd product with Sailthru for stores $storesString");
        }
    }

    /**
     * Catch loading of Catalog Product Admin and insert a new mass action to send to Sailthru.
     * @param Varien_Event_Observer $observer
     */
    public function addBlockMassAction(Varien_Event_Observer $observer) {
        /** @var Mage_Core_Block_Template $block */
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'catalog_product')
        {
            Mage::log([
                "class" => get_class($block),
                "controller" => $block->getRequest()->getControllerName()
            ], null, "sailthru.log");
            $block->addItem('sailthruemail', array(
                'label' => 'Send to Sailthru Content Library',
                'url' => Mage::app()->getStore()->getUrl('sailthruemail/content/bulk'),
            ));
        }
    }
    private function getScopedStores(Mage_Catalog_Model_Product $product)
    {
        $storeId = Mage::app()->getRequest()->getParam('store');
        if ($storeId) {
            return array($storeId);
        }

        $storeIds = $product->getStoreIds();
        $websiteId = Mage::app()->getRequest()->getParam('website');
        if ($websiteId) {
            $websiteStoreIds = Mage::getModel('core/website')->load($websiteId)->getStoreIds();
            return array_intersect($storeIds, $websiteStoreIds);
        }

        return $storeIds;
    }

    private function processContentError(Sailthru_Client_Exception $e)
    {
        $storeName = Mage::app()->getStore()->getName();

        Mage::getSingleton('core/session')->addError(
            self::FAILURE_MESSAGE . " for store {$storeName}." .
            " <pre >({$e->getCode()}) {$e->getMessage()}</pre>"
        );
        Mage::logException($e);
    }

}