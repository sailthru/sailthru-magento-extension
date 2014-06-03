<?php
/**
 * Email Log Resource Model Collection
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */

class Sailthru_Email_Model_Resource_Email_Log_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('sailthruemail/email_log');
    }
}
