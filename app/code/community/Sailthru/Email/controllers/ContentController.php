<?php
class Sailthru_Email_ContentController extends Mage_Adminhtml_Controller_Action {

    public function bulkAction() {
        $startTime = microtime(true);
        $data = $this->getRequest()->getPost();

        if ($data and array_key_exists('product', $data) and array_key_exists('massaction_prepare_key', $data)
            and $pCount = count($data['product']) and $data['massaction_prepare_key'] == 'product') {

            $savedProducts = 0;

            /** @var int[] $productIds */
            $productIds = $data['product'];
            foreach ($productIds as $productId) {
                /** @var Mage_Catalog_Model_Product $product */
                $product = Mage::getModel('catalog/product')->load($productId);
                $success = Mage::getModel('sailthruemail/observer_content')->syncProduct($product, true);
                if ($success) ++$savedProducts;
                usleep(0.25 * 1000000);
            }

            $endTime = microtime(true);
            $time = $endTime - $startTime;
            $message = "Succesfully sync'd $savedProducts / $pCount products to Sailthru";
            Mage::getSingleton('adminhtml/session')->addNotice($message.".");
            Mage::log($message . "in $time seconds", null, "sailthru.log");
        } else {
            unset($data['form_key']);
            Mage::getSingleton('adminhtml/session')->addError("Unable to send to Sailthru. Please try again. POST: {$data}");
        }
        $this->_redirectReferer();
    }
}