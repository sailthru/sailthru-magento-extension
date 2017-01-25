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
     * @return
     */
    public function subscription(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_email) {
            try{
                $subscriber = $observer->getEvent()->getSubscriber();
                $response = Mage::getModel('sailthruemail/client_user')->sendSubscriberData($subscriber);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Capture customer registration data
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function registration(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($this->_isEnabled && $customer->getEmail()) {
            try{
                $response = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Capture customer login data
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function login(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled) {
            try{
                if ($customer = $observer->getCustomer()) {
                    $response = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);
                    return true;
                } else {
                    return false;
                }
             } catch(Exception $e){
                 Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Capture customer logout data
     *
     * @param Varien_Event_Observer $observer
     * @return
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
        return $this;
    }
}