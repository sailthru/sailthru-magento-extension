<?php
/**
 * Primary Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer
{

    protected $_isEnabled = false;

    protected $_customerEmail = null;

    protected function __construct()
    {
        if(!Mage::helper('sailthruemail')->isEnabled()) {
            $this->_isEnabled = true;
            $this->_customerEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }
    }

    protected function _debug($object) {
        return Mage::helper('sailthruemail')->debug($object);
    }

    /**
     * Push new subscriber data to Sailthru
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function subscriberHandler(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_customerEmail) {
            try{
                $subscriber = $observer->getEvent()->getSubscriber();
                $response = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function customerHandler(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_customerEmail && Mage::helper('sailthruemail')->isEnabled()) {
            try{
                $customer = $observer->getEvent()->getCustomer();
                $response = Mage::getModel('sailthruemail/client_user')->sendSubscriberData($customer);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function pushIncompletePurchaseOrderToSailthru(Varien_Event_Observer $observer)
    {
        /**
         * Do nothing if user is not logged in. Purchase API requires an email
         * http://getstarted.sailthru.com/api/purchase
         */
        if($this->_isEnabled && $this->_customerEmail && Mage::helper('sailthruemail')->sendAbandonedCartEmails()) {
            try{
                $cart = $observer->getCart();
                $response = Mage::getModel('sailthruemail/client_purchase')->sendAbandonedCart($cart,$this->_customerEmail);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function pushPurchaseOrderSuccessToSailthru(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_customerEmail) {
            try{
                $cart = $observer->getCart();
                $customer = $observer->getEvent()->getCustomer();
                $response = Mage::getModel('sailthruemail/client_purchase')->sendOrderSuccess($cart, $customer, $this->_customerEmail);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }


    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled) {
            try{
                $product = $observer->getEvent()->getProduct();
                $response = Mage::getModel('sailthruemail/client_product')->deleteProduct($product);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function saveProduct(Varien_Event_Observer $observer)
    {
       if($this->_isEnabled) {
            try{
                $product = $observer->getEvent()->getProduct();
                $response = Mage::getModel('sailthruemail/client_product')->saveProduct($product);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function setSailthruCookie()
    {
        if($this->_isEnabled) {
            try{
                $response = Mage::getModel('sailthruemail/client')->setCookie($this->_customerEmail);
             }catch(Exception $e){
                 Mage::logException($e);
            }
        }
        return $this;
    }

    public function unsetSailthruCookie()
    {
        if($this->_isEnabled) {
            try{
                $response = Mage::getModel('sailthruemail/client')->deleteCookie();
             }catch(Exception $e){
                 Mage::logException($e);
            }
        }
        return $this;
    }
}