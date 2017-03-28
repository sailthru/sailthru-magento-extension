<?php

class Sailthru_Email_Model_Config_Source_Overridetemplates extends Sailthru_Email_Model_Client {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_eventType = "MagentoSettings";
        $response = $this->apiGet("template");
        if (isset($response["error"])){
            return [['value'=>0, 'label'=>__('Please Enter Valid API Credentials')]];
        }
        $templates = $response["templates"];
        $tpl_options = [
            ['value'=> 0, 'label'=>'Use Magento template']
        ];
        foreach ($templates as $tpl) {
            $tpl_options[] = [
                'value' => $tpl['name'],
                'label' => __($tpl['name'])
            ];
        }
        return $tpl_options;
    }
}