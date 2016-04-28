<?php

/*
* Generates form for sending newsletter blasts
*/

class Sailthru_Email_Block_Form extends Mage_Adminhtml_Block_Widget_Form
{

    public function _prepareForm()
    {

		$this->setTemplate("sailthruemail/form.phtml");
        $this->setFormAction(Mage::getUrl("*/*/post"));
        return parent::_prepareForm();

    }
}
