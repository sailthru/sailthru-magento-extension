<?php
    include_once("Mage/Customer/controllers/AccountController.php");
    class Sailthru_Email_AccountController extends Mage_Customer_AccountController {
        protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false) {
            $this->_getSession()->addSuccess(
                $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName()));
            //sailthru//
            $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
            $sailthru->setEmail($customer->getEmail(), array(
                "name" => $customer->getName(),
            ), array(Mage::getStoreConfig('sailthru_options/email/sailthru_def_list') => 1));
            $subscribed = $this->getRequest()->getParam('is_subscribed', false);
            if($subscribed) {
                $sailthru->setEmail($customer->getEmail(), array(
                    "name" => $customer->getName(),
                ), array(Mage::getStoreConfig('sailthru_options/email/sailthru_news_list') => 1));
            }
            //sailthru//
            $customer->sendNewAccountEmail($isJustConfirmed ? 'confirmed' : 'registered');
            $successUrl = Mage::getUrl('*/*/index', array('_secure'=>true));
            if ($this->_getSession()->getBeforeAuthUrl()) {
                $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
            }
            return $successUrl;
        }
    }
?>
