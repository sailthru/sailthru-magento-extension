<?php

class Sailthru_Email_Model_Config_Validkeys extends Sailthru_Email_Model_Config_Validkey
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
            $message = "Your Sailthru API credentials don't seem to be working. Please verify your API Key and Secret and try again.";
            if (!in_array($e->getCode(), [2, 3, 5])) {
                $message .= "<pre>({$e->getCode()}) {$e->getMessage()}</pre>";
            }
            Mage::getModel('core/session')->addError($message);
        }
        return parent::save();
    }
}