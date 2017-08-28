<?php

class Sailthru_Email_Model_Observer_Adminhtml
{
    /**
     * Catch loading of Catalog Product Admin and insert a new mass action to send to Sailthru.
     * @param Varien_Event_Observer $observer
     */
    public function addBlockMassAction(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();

        // verify this is for a specific store, or a single-store Magento instance
        $store = $block->getRequest()->getParam('store');
        if (!$store and count(Mage::app()->getStores()) == 1) {
            $store = 1;
        }

        // we only want to add this to the Product Catalog mass action block
        if($store != null and get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'catalog_product') {

            /** @var Mage_Adminhtml_Block_Widget_Grid_Massaction_Abstract $block */
            $block->addItem('sailthruemail_content_bulk', array(
                'label' => 'Send to Sailthru Content Library',
                'url' => $block->getUrl('sailthruemail/content/bulk'),
                'additional' => array(
                    'store' => array(
                        'name' => 'store',
                        'type' => 'hidden',
                        'value' => $store
                ))));
        }
    }
}