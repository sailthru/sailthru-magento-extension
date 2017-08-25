<?php
class Sailthru_Email_ContentController extends Mage_Adminhtml_Controller_Action {

    public function bulkAction() {
        $data = $this->getRequest()->getPost();
        Mage::log($data, null, "sailthru.log");
    }
}