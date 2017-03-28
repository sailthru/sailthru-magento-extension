<?php
/**
 * User API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_User extends Sailthru_Email_Model_Abstract
{
    /**
     * Capture customer subscription data
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function subscription(Varien_Event_Observer $observer)
    {
        if ($this->_isEnabled) {
            $subscriber = $observer->getEvent()->getSubscriber();
            if ($this->_isEnabled && $subscriber->getEmail()) {
                try {
                    Mage::log("\n\n**Subscription Fired!**\n\n", null, "sailthru.log");
                    $subscriber = $observer->getEvent()->getSubscriber();
                    $response = Mage::getModel('sailthruemail/client_user')->sendSubscriberData($subscriber);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Capture customer registration data
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function registration(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($this->_isEnabled && $customer->getEmail()) {
            try{
                $response = Mage::getModel('sailthruemail/client_user')->postNewCustomer($customer);
            } catch (Exception $e) {
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
        if($this->_isEnabled) {
            try{
                if ($customer = $observer->getCustomer()) {
                    $response = Mage::getModel('sailthruemail/client_user')->loginCustomer($customer);
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
                $response = Mage::getModel('sailthruemail/client_user')->logout();
             }catch(Exception $e){
                 Mage::logException($e);
            }
        }
    }
}