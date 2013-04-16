<?php
class Sailthru_Email_Model_Mysql4_Email_Log
    extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Resource model initialization 
     */
    protected function _construct()
    {
         $this->_init('sailthruemail/email_log', 'email_id');
    }
}
