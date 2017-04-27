<?php

/**
 * Used in creating options for List Selection from Sailthru, or inactive if API keys are invalid.
 *
 */
class Sailthru_Email_Model_Config_Source_Sailthrutemplates extends Sailthru_Email_Model_Config_Source
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
            $response = $this->apiGet("template");
            if (isset($response["error"])) {
                return [ [ 'value' => 0, 'label' => __('Please Enter Valid API Credentials') ] ];
            }
            $templates = $response["templates"];
            $tpl_options = [
                [ 'value' => 0, 'label' => '' ]
            ];
            foreach ($templates as $tpl) {
                $tpl_options[] = [
                    'value' => $tpl['name'],
                    'label' => __($tpl['name'])
                ];
            }
            return $tpl_options;
        } catch (Sailthru_Client_Exception $e) {
            $this->processOptionArrayError($e);
        }
    }
}