<?php

class Sailthru_Email_Model_Config_Validkeys extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $apiSecret = $this->getValue();
        $apiKey = $this->getData('groups/api/fields/key/value');
        $apiUri = $this->getData('groups/api/fields/uri/value');
        try {
            $client = Mage::getModel('sailthruemail/client');
            $client->log([$apiSecret, $apiKey, $apiUri]);
            $client->testKeys($apiKey, $apiSecret, $apiUri);
            Mage::getModel('core/session')->addSuccess("Sailthru API Keys validated.");
        } catch (Sailthru_Client_Exception $e) {
            Mage::getModel('core/session')->addError("Your Sailthru API Key and Secret don't seem to be working. Please verify and try again. <pre>({$e->getCode()}) {$e->getMessage()}</pre>");
        }

        return parent::save();
    }
}