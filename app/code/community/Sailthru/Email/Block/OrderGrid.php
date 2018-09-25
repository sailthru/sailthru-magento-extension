<?php

class Sailthru_Email_Block_OrderGrid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    protected function _getCollectionClass()
    {
        return 'sales/order_collection';
    }


    public function getAllOrderIds()
    {
        $this->_prepareGrid();
        return $this->getCollection()->getAllIds();
    }

    public function getQueriedCollection()
    {
        $this->_prepareGrid();
        return $this->getCollection();
    }

    /**
     * @return Varien_Db_Select
     */
    public function getCollectionQuery()
    {
        $this->sortColumnsByOrder();
        $this->_prepareCollection();
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = $this->getCollection();
        Mage::log([
            $collection->getFilter([]),
        ], null, "url.log");
    }
}