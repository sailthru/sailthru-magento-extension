<?php
class Sailthru_Email_ContentController extends Mage_Adminhtml_Controller_Action
{
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sailthruemail/sailthru_content');
    }

    /**
     * <magento_uri>/content/bulk endpoint action
     */
    public function bulkAction() 
    {
        $data = $this->getRequest()->getPost();
        if ($data and array_key_exists('product', $data) and
            array_key_exists('massaction_prepare_key', $data) and $data['massaction_prepare_key'] == 'product' and
            array_key_exists('store', $data) and (int) $data['store'] > 0) {
            $this->_processItems($data['product'], $data['store']);
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError("Request to bulk update Sailthru was malformed. Please try again.");
        }

        $this->_redirectReferer();
    }

    /**
     * Send products to Sailthru by ID number and a given store
     * @param int[] $productIds
     * @param int $store
     */
    protected function _processItems($productIds, $store)
    {
        /** @var Mage_Core_Model_App_Emulation $appEmulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $emulateData = $appEmulation->startEnvironmentEmulation($store);
        $count = count($productIds);
        $startTime = microtime(true);

        $syncedProducts = 0;

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')
            ->getCollection();

        $collection
            ->addAttributeToSelect("*")
            ->addIdFilter($productIds)
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->setPageSize(75);

        $page = 1;
        do {
            $collection->setCurPage($page++)->load();

            /** @var Mage_Catalog_Model_Product $product */
            foreach ($collection->getItems() as $product) {
                try {
                    if ($product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                        $success = Mage::getModel('sailthruemail/client_content')->saveProduct($product);
                        if ($success) {
                            $syncedProducts++;
                        }

                    } elseif ($product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                        try {
                            $success = Mage::getModel('sailthruemail/client_content')->deleteProduct($product);
                            if ($success) {
                                $syncedProducts++;
                            }
                        } catch (Sailthru_Client_Exception $e) {
                            if (Mage::helper('sailthruemail')->isContentNotExistError($e)) {
                                // content isn't in Sailthru yet anyways, so equivalent to sync.
                                $syncedProducts++;
                            } else {
                                throw($e); // bubble up
                            }
                        }
                    }
                } catch (Sailthru_Client_Exception $e) {
                    Mage::getSingleton('adminhtml/session')
                        ->addError(
                            "Error saving {$product->getName()} ({$product->getSku()})" .
                            "<pre >({$e->getCode()}) {$e->getMessage()}</pre>"
                        );
                }
            }

            $collection->clear();
        } while ($page <= $collection->getLastPageNumber());

        $endTime = microtime(true);
        $time = $endTime - $startTime;
        $appEmulation->stopEnvironmentEmulation($emulateData);
        $message = "Succesfully sync'd $syncedProducts / $count products to Sailthru";
        Mage::getSingleton('adminhtml/session')->addNotice($message.".");
        Mage::log($message . "in $time seconds", null, "sailthru.log");
    }
}