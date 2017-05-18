<?php

class Sailthru_Email_Model_Config_Source_Overridetemplates extends Sailthru_Email_Model_Config_Source
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_eventType = "System Config -> Transactionals -> Override Templates";
        try {
            $response = $this->apiGet("template");
            $templates = $response["templates"];
            $tpl_options = array(
                array('value'=> 0, 'label'=>'Use Magento template')
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