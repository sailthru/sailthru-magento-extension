<?php

class Sailthru_Email_OrderController extends Mage_Adminhtml_Controller_Action
{
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sailthruemail/sailthru_order');
    }

    /**
     * <magento_uri>/content/bulk endpoint action
     * @throws Exception
     */
    public function bulkAction()
    {
        $store = $this->getRequest()->getParam('store');
        $json  = implode("\n", $this->_processOrders($store));
        $this->_prepareDownloadResponse('orders.json', $json);
    }

    /**
     * Send products to Sailthru by ID number and a given store
     * @param int $store
     * @return array
     * @throws Exception
     */
    protected function _processOrders($store)
    {
        /** @var Mage_Core_Model_App_Emulation $appEmulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $emulateData = $appEmulation->startEnvironmentEmulation($store);

        $startTime = microtime(true);

        /** @var Sailthru_Email_Block_OrderGrid $grid */
        $grid = $this->getLayout()->createBlock('sailthruemail/OrderGrid');
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = $grid->getQueriedCollection();
        $collection
            ->addFieldToFilter('store_id', $store)
            ->addFieldToFilter('status', ["neq" => 'canceled']);

        if (!$collection->count()) {
            $name = Mage::app()->getStore($store)->getName();
            Mage::getSingleton('adminhtml/session')
                ->addError("Sailthru export error: There are no orders to export for your store $name");
            $this->_redirectReferer();
        }

        $queryTime = microtime(true) - $startTime;
        Mage::log("Queried Order Collection in $queryTime seconds", null, "sailthru.log");

        $exportData = Mage::helper('sailthruemail/purchase')->generateExportData($collection);
        $appEmulation->stopEnvironmentEmulation($emulateData);
        return $exportData;
    }



    protected function getUiQueriedOrderIds()
    {
        /** @var Sailthru_Email_Block_OrderGrid $grid */
        $grid = $this->getLayout()->createBlock('sailthruemail/ordergrid');
        $ids = $grid->getAllOrderIds();
        return $ids;
    }
}