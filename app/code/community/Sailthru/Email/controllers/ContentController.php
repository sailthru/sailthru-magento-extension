<?php
class Sailthru_Email_ContentController extends Mage_Adminhtml_Controller_Action {

    const MAX_ITEM_POST = 200;

    /**
     * <magento_uri>/content/bulk endpoint action
     */
    public function bulkAction() {
        $data = $this->getRequest()->getPost();
        Mage::log([
            $this->getRequest()->getStoreCodeFromPath(),
            Mage::getSingleton('adminhtml/config_data')->getStore(),
            Mage::app()->getRequest()->getParam('store'),
            Mage::app()->getStore()->debug(),
        ], null, "sailthru.log");

        Mage::log($data,null, "sailthru.log");
        if ($data and array_key_exists('product', $data) and array_key_exists('massaction_prepare_key', $data)
            and $pCount = count($data['product']) and $data['massaction_prepare_key'] == 'product') {
            $waitForResponse = $this->_processItems($data['product']);
        } else {
            unset($data['form_key']);
            Mage::getSingleton('adminhtml/session')->addError("Request was malformed. Please try again. POST: {$data}");
        }
        $this->_redirectReferer();
    }

    private function _processItems($productIds) {
        $count = count($productIds);
        if ($count > self::MAX_ITEM_POST) {
            Mage::getSingleton('adminhtml/session')->addError("Unable to send to Sailthru - please select " . self::MAX_ITEM_POST . " items max. ($count selected)");
            return;
        }
        $startTime = microtime(true);
        $savedProducts = 0;

        /** @var int[] $productIds */
        foreach ($productIds as $productId) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($productId);
            $success = Mage::getModel('sailthruemail/observer_content')->syncProduct($product, true);
            if ($success) ++$savedProducts;
            usleep(0.25 * 1000000);
        }

        $endTime = microtime(true);
        $time = $endTime - $startTime;
        $message = "Succesfully sync'd $savedProducts / $count products to Sailthru";
        Mage::getSingleton('adminhtml/session')->addNotice($message.".");
        Mage::log($message . "in $time seconds", null, "sailthru.log");
    }

}