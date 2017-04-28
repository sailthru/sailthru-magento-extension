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
            $this->_eventType = "MagentoSettings";
            $response = $this->apiGet("list");
            $lists = $response["lists"];
            $lists_options = [['value'=> 0, 'label'=>' ']];
            foreach ($lists as $list) {
                if ($list['type'] == 'normal') {
                    $lists_options[] = [
                        'value' => $list['name'],
                        'label' => __("{$list['name']} ({$list['email_count']} Emails)")
                    ];
                }
            }
            return $lists_options;
        } catch (Sailthru_Client_Exception $e) {
            return $this->processOptionArrayError($e);
        }
    }
}