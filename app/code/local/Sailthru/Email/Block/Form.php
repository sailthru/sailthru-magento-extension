<?php
    class Sailthru_Email_Block_Form extends Mage_Adminhtml_Block_Template {
        public function __construct() {
            parent::__construct();
            $this->setTemplate("sailthru_email/form.phtml");
            $this->setFormAction(Mage::getUrl("*/*/post"));
        }
    }
?>
