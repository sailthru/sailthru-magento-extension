<?php
/**
 * Email Log Resource Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */

class Sailthru_Email_Model_Resource_Email_Log extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Resource model initialization
     */
    protected function _construct()
    {
         $this->_init('sailthruemail/email_log', 'email_id');
    }
}