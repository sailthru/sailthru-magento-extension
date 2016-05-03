<?php
class Sailthru_Email_AdminController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock("sailthruemail/form"))->renderLayout();
    }

    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            $time = $post["day"]."-".$post["month"]."-".$post["year"]." ".$post["hour"].":".$post["minute"].$post["apm"];
            $success =  Mage::getModel('sailthruemail/client')->scheduleBlast("magento-blast-".date("mdY"), $post["list"], $time, NULL, NULL, NULL, NULL, NULL, array("copy_template" => $post["template"]));
            if(count($success) == 2) {
                Mage::throwException($this->__($success["errormsg"]));
            }
            //sailthru//
            $message = $this->__('Your form has been submitted successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
}
