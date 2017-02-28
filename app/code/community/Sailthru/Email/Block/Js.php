<?php
/**
 * Sailthru Extension for Magento 
 * 
 * @category Sailthru
 * @package Sailthru_Email
 * @author Kwadwo Juantuah <support@sailthru.com>
 */


class Sailthru_Email_Block_Js extends Mage_Page_Block_Html
{
    protected function _construct() {
        $sailthruHelper = Mage::helper('sailthruemail');
        if ($sailthruHelper->isPersonalizeJsEnabled() and $sailthruHelper->getCustomerId()) {
            Mage::log("SPM time!", null, "sailthru.log");
            $this->setTemplate("sailthru/spm.phtml");
        } elseif ($sailthruHelper->isHorizonEnabled() and $sailthruHelper->getHorizonDomain()) {
            $this->setTemplate("sailthru/horizon.phtml");
        }
        parent::_construct();
    }

}