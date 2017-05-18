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
            $this->_eventType = "System Config -> Transactionals -> Templates";
            $response = $this->apiGet("template", array());
            $templates = $response["templates"];
            $tpl_options = array(
                array( 'value' => null, 'label' => 'Please select a template' )
            );
            foreach ($templates as $tpl) {
                $tpl_options[] = array(
                    'value' => $tpl['name'],
                    'label' => __($tpl['name'])
                );
            }

            return $tpl_options;
        } catch (Sailthru_Client_Exception $e) {
            return $this->processOptionArrayError($e);
        }
    }
}