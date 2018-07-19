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
    public function bulkAction($store=null)
    {
        $fileName = 'orders.csv';
        /** @var Mage_Adminhtml_Block_Sales_Order_Grid $grid */
        $store = $this->getRequest()->getParam('store');
        $json = implode("\n", $this->_processOrders($store));
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

        $syncedOrders = 0;
        $orderData = [];

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')
            ->getCollection();

        $collection
            ->addAttributeToSelect("*")
            ->setPageSize(75);

        $page = 1;

        $purchaseClient = Mage::getModel('sailthruemail/client_purchase');
        do {
            $collection->setCurPage($page++)->load();
            /** @var Mage_Sales_Model_Order $order */
            foreach ($collection->getItems() as $order) {
                $data = [
                    'date' => $order->getCreatedAtStoreDate()->getIso(),
                    'email' => $order->getCustomerEmail(),
                    'items' => $purchaseClient->getItems($order->getAllVisibleItems()),
                    'adjustments' => Mage::helper('sailthruemail/purchase')->getAdjustments($order, "api"),
                    'tenders' => Mage::helper('sailthruemail/purchase')->getTenders($order),
                    'purchase_keys' => array("extid" => $order->getIncrementId())
                ];

                $orderData[] = json_encode($data);
                $syncedOrders++;
                if ($syncedOrders % 5 == 0) Mage::log("Processed $syncedOrders orders", null, "url.log");

            }

            $collection->clear();
        } while ($page <= $collection->getLastPageNumber());

        $endTime = microtime(true);
        $time = $endTime - $startTime;
        $appEmulation->stopEnvironmentEmulation($emulateData);
        Mage::getSingleton('adminhtml/session')->addNotice("Successfully generated Sailthru JSON");
//        Mage::log($message . "in $time seconds", null, "sailthru.log");
        return $orderData;
    }
}