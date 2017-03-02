<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Productattributes
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $results = [];
        $defaultId = intval(Mage::getModel('catalog/product')->getDefaultAttributeSetId()); //todo add filtering
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
        foreach($attributes as $attribute) {
            if ($name = $attribute->getFrontendLabel()) {
                $results[] = [
                    'value' => $attribute->getId(),
                    'label' => Mage::helper('adminhtml')->__($name)
                ];
            }
        }
        return $results;
    }
}