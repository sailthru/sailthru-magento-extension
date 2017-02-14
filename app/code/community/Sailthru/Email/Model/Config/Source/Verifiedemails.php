<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Verifiedemails extends Sailthru_Email_Model_Client
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_eventType = "MagentoSettings";
        $response = $this->apiGet("settings");
        if (isset($response["error"])){
            return [['value'=>0, 'label'=>__('Please Enter Valid API Credentials')]];
        }
        $emails = $response["from_emails"];
        $sender_options = [
            ['value'=> 0, 'label'=>' ']
        ];
        foreach ($emails as $key => $email) {
            $sender_options[] = [
                'value' => $email,
                'label' => $email
            ];
        }
        return $sender_options;
    }
}