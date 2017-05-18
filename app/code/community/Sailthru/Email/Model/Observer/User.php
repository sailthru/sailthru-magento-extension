<?php
/**
 * User API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_User extends Sailthru_Email_Model_Observer
{

    /**
     * Capture customer subscription data
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function subscription(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $subscribeEnabled = Mage::helper('sailthruemail')->isNewsletterListEnabled($this->storeId);
        $subscribeList = Mage::helper('sailthruemail')->getNewsletterList($this->storeId);
        if ($this->isEnabled and $subscriber and $subscribeEnabled and $subscribeList) {
            try {
                Mage::getModel('sailthruemail/client_user')->sendSubscriberData($subscriber);
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Capture customer registration data. Whether to add to Master List happens in Client_User
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function registration(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($this->isEnabled and $customer) {
            try {
                Mage::getModel('sailthruemail/client_user')->postNewCustomer($customer);
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Capture customer updates
     * @param Varien_Event_Observer $observer
     */
    public function update(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer() ?: $observer->getEvent()->getCustomerAddress()->getCustomer();
        if ($this->isEnabled and $customer) {
            try {
                Mage::getModel('sailthruemail/client_user')->updateCustomer($customer);
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Capture customer login data
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function login(Varien_Event_Observer $observer)
    {
        if($this->isEnabled and $customer = $observer->getCustomer()) {
            try {
                Mage::getModel('sailthruemail/client_user')->loginCustomer($customer);
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }
        }
    }

}