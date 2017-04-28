<?php
/**
 * Primary Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer
{
    /** @var bool isEnabled */
    protected $isEnabled = false;

    /** @var int storeId  */
    protected $storeId = null;

    public function __construct()
    {
        $this->storeId = Mage::app()->getStore()->getId();
        $this->isEnabled = Mage::helper('sailthruemail')->isEnabled($this->storeId);
    }

}