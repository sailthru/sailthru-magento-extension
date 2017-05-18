<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Sailthrulists extends Sailthru_Email_Model_Config_Source
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        try {
            $this->_eventType = "System Config -> Users -> Lists";
            $response = $this->apiGet("list");
            $lists = $response["lists"];
            $lists_options = array(array('value'=> null, 'label'=>'Please select a list'));
            foreach ($lists as $list) {
                if ($list['type'] == 'normal') {
                    $lists_options[] = array(
                        'value' => $list['name'],
                        'label' => __("{$list['name']} ({$list['email_count']} Emails)")
                    );
                }
            }

            return $lists_options;
        } catch (Sailthru_Client_Exception $e) {
            return $this->processOptionArrayError($e);
        }
    }
}