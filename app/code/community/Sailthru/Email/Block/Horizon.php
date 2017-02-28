<?php
/**
 * Sailthru Extension for Magento 
 * 
 * @category Sailthru
 * @package Sailthru_Email
 * @author Kwadwo Juantuah <support@sailthru.com>
 */


class Sailthru_Email_Block_Horizon extends Mage_Page_Block_Html_Head
{
    private $sailthruHelper;

    protected function _construct() {
        $sailthruHelper = Mage::helper('sailthruemail');
        if ($sailthruHelper->isPersonalizeJsEnabled() and $sailthruHelper->getCustomerId()) {
            Mage::log('loading SPM!', null, 'sailthru.log');
            $this->setTemplate("sailthru/spm.phtml");
        } elseif ($sailthruHelper->isHorizonEnabled() and $sailthruHelper->getHorizonDomain()) {
            Mage::log("loading Horizon!", null, "sailthru.log");
            $this->setTemplate("sailthru/horizon.phtml");
        }
    }

}