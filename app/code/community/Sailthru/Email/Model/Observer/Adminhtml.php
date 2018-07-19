<?php

class Sailthru_Email_Model_Observer_Adminhtml
{
    const BLOCK__MASS_ACTION    = "Mage_Adminhtml_Block_Widget_Grid_Massaction";
    const CONTENT_CONTROLLER    = "catalog_product";
    const SALES_CONTROLLER      = "sales_order";

    /**
     * Catch loading of Catalog Product Admin and insert a new mass action to send to Sailthru.
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function addBlockMassAction(Varien_Event_Observer $observer) 
    {
        $block = $observer->getEvent()->getBlock();
        $singleStore = Mage::app()->isSingleStoreMode();
        $store = $singleStore ? 1 : $block->getRequest()->getParam('store');
        
        if ($store and $this->isMassActionWidget($block) and $this->isController($block, self::CONTENT_CONTROLLER) and $this->contentActionEnabled()) {
            $this->setupContentSync($block, $store);
        }

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                /** @var $store Mage_Core_Model_Store */
                $name = "Sailthru JSON" . (Mage::app()->isSingleStoreMode() ? "" : " [ {$store->getName()}]");
                $block->addExportType("sailthruemail/order/bulk/store/{$store->getId()}", $name);
            }
        }
    }

    private function setupContentSync(Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract $block, $store)
    {
        $block->addItem(
            'sailthruemail_content_bulk', array(
                'label' => '[BETA] Send to Sailthru',
                'url' => $block->getUrl('sailthruemail/content/bulk'),
                'additional' => array(
                    'store' => array(
                        'name' => 'store',
                        'type' => 'hidden',
                        'value' => $store
                    )))
        );
    }

    private function setupOrderExport(Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract $block)
    {
        /** @var Mage_Core_Model_Store[] $stores */
        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $block->addItem(
                "sailthruemail_sales_bulk_{$store->getId()}", array(
                    'label' => "Sailthru CSV [{$store->getName()}]",
                    'url' => $block->getUrl('sailthruemail/order/bulk'),
                    'additional' => array(
                        'store' => array(
                            'name' => 'store',
                            'type' => 'hidden',
                            'value' => $store->getId()
                        )))
            );
        }

    }

    /**
     * @param Mage_Core_Block_Abstract $block
     * @param $type
     * @return bool
     */
    private function isMassActionWidget(Mage_Core_Block_Abstract $block)
    {
        return $block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract;
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     * @param $controller
     * @return bool
     * @throws Exception
     */
    private function isController(Mage_Core_Block_Abstract $block, $controller)
    {
        return $block->getRequest()->getControllerName() == $controller;
    }

    private function contentActionEnabled()
    {
        return Mage::helper('sailthruemail')->isEnabled() and Mage::helper('sailthruemail')->isProductMassActionEnabled();

    }

    private function isSingleStore()
    {
        return count(Mage::app()->getStores()) == 1;
    }
}

