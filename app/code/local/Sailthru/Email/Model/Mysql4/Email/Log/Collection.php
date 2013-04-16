<?php
class Sailthru_Email_Model_Mysql4_Email_Log_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Collection constructor
     *
     */
    protected function _construct()
    {
        $this->_init('sailthruemail/email_log');
    }
}
