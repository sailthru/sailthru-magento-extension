<?php

class Sailthru_Email_Model_Config_Validkey extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $this->keyLengthValidate();
        return parent::save();
    }

    protected function keyLengthValidate()
    {
        $key = $this->getValue();
        if ($size = strlen($key) != 32) {
            $pathParts = explode("/", $this->getPath());
            $field = $pathParts[2];
            Mage::throwException("API {$field} must be 32 characters long.");
        }
    }
}