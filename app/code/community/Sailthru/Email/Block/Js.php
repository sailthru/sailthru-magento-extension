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
    protected function _construct() 
    {
        $sailthruHelper = Mage::helper('sailthruemail');
        if ($sailthruHelper->isSailthruScriptTagEnabled() and $sailthruHelper->getCustomerId()) {
            $this->setTemplate("sailthru/Sst.phtml");
        } elseif ($sailthruHelper->isHorizonEnabled() and $sailthruHelper->getHorizonDomain()) {
            $this->setTemplate("sailthru/Horizon.phtml");
        }

        parent::_construct();
    }

}