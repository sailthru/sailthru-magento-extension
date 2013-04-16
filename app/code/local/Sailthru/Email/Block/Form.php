<?php
class Sailthru_Email_Block_Form extends Mage_Adminhtml_Block_Template
{
    /**
     * Internal constructor, that is called from real constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate("sailthruemail/form.phtml");
        $this->setFormAction(Mage::getUrl("*/*/post"));
    }
}
