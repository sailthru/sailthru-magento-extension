<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Verifiedemails extends Sailthru_Email_Model_Config_Source
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        try {
            $this->_eventType = "MagentoSettings";
            $response = $this->apiGet("settings");
            $emails = $response["from_emails"];
            $sender_options = [
                ['value'=> null, 'label'=>'Please select an email address']
            ];
            foreach ($emails as $key => $email) {
                $sender_options[] = [
                    'value' => $email,
                    'label' => $email
                ];
            }
            return $sender_options;
        } catch (Sailthru_Client_Exception $e) {
                return $this->processOptionArrayError($e);
        }
    }
}