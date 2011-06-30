<?php
    include_once("Mage/Newsletter/controllers/SubscriberController.php");
    class Sailthru_Email_SubscriberController extends Mage_Newsletter_SubscriberController {
        public function newAction() {
            if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
                $session            = Mage::getSingleton('core/session');
                $customerSession    = Mage::getSingleton('customer/session');
                $email              = (string) $this->getRequest()->getPost('email');
                try {
                    if (!Zend_Validate::is($email, 'EmailAddress')) {
                        Mage::throwException($this->__('Please enter a valid email address.'));
                    }

                    if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 && 
                        !$customerSession->isLoggedIn()) {
                        Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::helper('customer')->getRegisterUrl()));
                    }

                    $ownerId = Mage::getModel('customer/customer')
                            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                            ->loadByEmail($email)
                            ->getId();
                    if ($ownerId !== null && $ownerId != $customerSession->getId()) {
                        Mage::throwException($this->__('This email address is already assigned to another user.'));
                    }

                    $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                    if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                        $session->addSuccess($this->__('Confirmation request has been sent.'));
                    }
                    else {
                        $session->addSuccess($this->__('Thank you for your subscription.'));
                        //sailthru//
                        $list = Mage::getStoreConfig('sailthru_options/email/sailthru_news_list');
                        $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
                        $sailthru->setEmail($email, array(), array($list => 1));
                        //sailthru//
                    }
                }
                catch (Mage_Core_Exception $e) {
                    $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                }
                catch (Exception $e) {
                    $session->addException($e, $this->__('There was a problem with the subscription.'));
                }
            }
            $this->_redirectReferer();
        }

        /**
         * Unsubscribe newsletter
         */
        public function unsubscribeAction()
        {
            $id    = (int) $this->getRequest()->getParam('id');
            $code  = (string) $this->getRequest()->getParam('code');

            if ($id && $code) {
                $session = Mage::getSingleton('core/session');
                try {
                    Mage::getModel('newsletter/subscriber')->load($id)
                        ->setCheckCode($code)
                        ->unsubscribe();
                    //sailthru//
                    $email = Mage::getModel('newsletter/subscriber')->load($id)->getEmail();
                    $list = Mage::getStoreConfig('sailthru_options/email/sailthru_news_list');
                    $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
                    $sailthru->setEmail($email, array(), array($list => 0));
                    //sailthru//
                    $session->addSuccess($this->__('You have been unsubscribed.'));
                }
                catch (Mage_Core_Exception $e) {
                    $session->addException($e, $e->getMessage());
                }
                catch (Exception $e) {
                    $session->addException($e, $this->__('There was a problem with the un-subscription.'));
                }
            }
            $this->_redirectReferer();
        }
    }
?>
