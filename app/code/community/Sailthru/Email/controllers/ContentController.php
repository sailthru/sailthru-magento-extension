<?php
class Sailthru_Email_ContentController extends Mage_Adminhtml_Controller_Action {

    const MAX_ITEM_POST = 350;

    /**
     * <magento_uri>/content/bulk endpoint action
     */
    public function bulkAction() {
        $data = $this->getRequest()->getPost();
        if ($data and array_key_exists('product', $data) and
            array_key_exists('massaction_prepare_key', $data) and $data['massaction_prepare_key'] == 'product' and
            array_key_exists('store', $data) and intval($data['store']) > 0) {
            $this->_processItems($data['product'], $data['store']);
        } else {
            Mage::getSingleton('adminhtml/session')->addError("Request to bulk update Sailthru was malformed. Please try again.");
        }
        $this->_redirectReferer();
    }

    /**
     * Send products to Sailthru by ID number and a given store
     * @param int[] $productIds
     * @param int $store
     */
    private function _processItems($productIds, $store) {
        if (($count = count($productIds)) > self::MAX_ITEM_POST) {
            Mage::getSingleton('adminhtml/session')->addError("Unable to send to Sailthru - please select " . self::MAX_ITEM_POST . " items max. ($count selected)");
            return;
        }

        /** @var Mage_Core_Model_App_Emulation $appEmulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $emulateData = $appEmulation->startEnvironmentEmulation($store);
        $count = count($productIds);
        $startTime = microtime(true);

        $savedProducts = 0;
        $erroredProducts = array();
        /** @var int[] $productIds */
        foreach ($productIds as $productId) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($productId);
            try {
                $success = Mage::getModel('sailthruemail/client_content')->saveProduct($product);
                if ($success) $savedProducts++;
            } catch (Sailthru_Client_Exception $e) {
                Mage::getSingleton('adminhtml/session')
                    ->addError("Error saving {$product->getName()} ({$product->getSku()})" .
                        "<pre >({$e->getCode()}) {$e->getMessage()}</pre>"
                    );
            }
            usleep(0.25 * 1000000);
        }
        $endTime = microtime(true);
        $time = $endTime - $startTime;
        $appEmulation->stopEnvironmentEmulation($emulateData);
        $message = "Succesfully sync'd $savedProducts / $count products to Sailthru";
        Mage::getSingleton('adminhtml/session')->addNotice($message.".");
        Mage::log($message . "in $time seconds", null, "sailthru.log");
    }

}