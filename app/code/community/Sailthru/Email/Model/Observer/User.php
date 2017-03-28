<?php
/**
 * User API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_User extends Sailthru_Email_Model_Abstract {
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
        if ($this->_isEnabled and $subscriber) {
            $response = Mage::getModel('sailthruemail/client_user')->sendSubscriberData($subscriber);
        }
    }

    /**
     * Capture customer registration data
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function registration(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($this->_isEnabled and $customer) {
            Mage::getModel('sailthruemail/client_user')->postNewCustomer($customer);
        }
    }

    /**
     * Capture customer updates
     * @param Varian_Event_Observer $observer
     */
    public function update(Varian_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($this->_isEnabled and $customer) {
            Mage::getModel('sailthruemail/client_user')->updateCustomer($customer);
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
        if($this->_isEnabled) {
            try{
                if ($customer = $observer->getCustomer()) {
                    Mage::getModel('sailthruemail/client_user')->loginCustomer($customer);
                }
             } catch(Exception $e){
                 Mage::logException($e);
            }
        }
    }

    /**
     * Capture customer logout data
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function logout()
    {
        if($this->_isEnabled) {
            try{
                Mage::getModel('sailthruemail/client_user')->logout();
             }catch(Exception $e){
                 Mage::logException($e);
            }
        }
    }
}