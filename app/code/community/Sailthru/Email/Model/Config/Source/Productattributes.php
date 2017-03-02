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
        $attributeSet = $this->getNonDefaultAttributes();
        foreach($attributeSet as $code => $label) {
            $results[] = [
                'value' => $code,
                'label' => Mage::helper('adminhtml')->__($label)
            ];
        }
        return $results;
    }

    public static function getNonDefaultAttributes()
    {
        $allAttributesSet = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();

        $defaultId = intval(Mage::getModel('catalog/product')->getDefaultAttributeSetId());
        $defaultSet = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection($defaultId);

        $allAttributes =  [];
        foreach ($allAttributesSet as $attribute) {
            $allAttributes[$attribute->getAttributeCode()] = $attribute->getStoreLabel() ?: $attribute->getFrontendLabel();
        }
        $defaultAttributes = [];
        foreach ($defaultSet as $dAttribute) {
            $defaultAttributes[$dAttribute->getAttributeCode()] = $dAttribute->getStoreLabel() ?: $dAttribute->getFrontendLabel();
        }

        return array_diff_key($allAttributes, $defaultAttributes);
    }

}

