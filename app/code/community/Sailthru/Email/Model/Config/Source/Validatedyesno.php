<?php

class Sailthru_Email_Model_Config_Source_Validatedyesno extends Sailthru_Email_Model_Config_Source
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        try {
            $this->testKeys();
            return array(
                array( 'value' => 1, 'label' => Mage::helper('adminhtml')->__('Yes') ),
                array( 'value' => 0, 'label' => Mage::helper('adminhtml')->__('No') ),
            );
        } catch (Sailthru_Client_Exception $e) {
            return $this->processOptionArrayError($e);
        }
    }
}