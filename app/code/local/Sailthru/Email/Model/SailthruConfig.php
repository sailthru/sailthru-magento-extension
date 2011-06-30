<?php
    class Sailthru_Email_Model_SailthruConfig extends Mage_Core_Model_Abstract {
        protected function _construct() {
            $this->_init("email/sailthruconfig");
        }
        public function getHandle() {
            include_once("sailthru_api/Sailthru_Client_Exception.php");
            include_once("sailthru_api/Sailthru_Client.php");
            include_once("sailthru_api/Sailthru_Util.php");  
            $handle = new Sailthru_Client(Mage::getStoreConfig('sailthru_options/api/sailthru_api_key'), Mage::getStoreConfig('sailthru_options/api/sailthru_api_secret'));
            return $handle;
        }
    }
?>
