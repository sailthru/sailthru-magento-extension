<?php

class Sailthru_Email_Model_Observer_Adminhtml
{
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
            $massAction = [
                'label' => 'Send to Sailthru Content Library',
                'url' => Mage::app()->getStore()->getUrl('sailthruemail/content/bulk')
            ];

            /** @var Mage_Core_Model_Store[] $stores */
            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                Mage::log($store->debug(), null, "sailthru.log");
            }
            if (count($stores) > 1) {
                $massAction['additional'] = [
                    'store' => [
                        'name' => 'store',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => Mage::helper('catalog')->__('Store'),
                        'values' => Mage::getResourceModel('core/store_collection')->load()->toOptionArray()
                    ]
                ];
            }
            $block->addItem('sailthruemail', $massAction);
        }
    }
}