<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Sailthrulists extends Sailthru_Email_Model_Client
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_eventType = "MagentoSettings";
        $response = $this->apiGet("list");
        if (isset($response["error"])){
            return [['value'=>0, 'label'=>__('Please Enter Valid API Credentials')]];
        }
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
    }
}