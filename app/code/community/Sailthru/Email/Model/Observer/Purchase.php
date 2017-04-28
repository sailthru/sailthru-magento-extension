<?php
/**
 * Purchase API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Purchase extends Sailthru_Email_Model_Observer {

    public function isCartEnabled()
    {
        return ($this->isEnabled and (Mage::helper('sailthruemail')->isAbandonedCartEnabled() or Mage::helper('sailthruemail')->isAnonymousCartEnabled()));
    }

    public function emptyCart(Varien_Event_Observer $observer)
    {
        $quote = $observer->getCart()->getQuote();
        $num_prods = $quote->getItemsCount();
        $num_qty = $quote->getItemsQty();
        if($quote->getItemsCount() == 0 && $this->isCartEnabled()) {
            try{
                 $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($quote,$this->_email,'EmptiedCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function addItemToCart(Varien_Event_Observer $observer)
    {
        if($this->isCartEnabled()) {
            try{
                $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(),$this->_email,'addItemToCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function updateItemInCart(Varien_Event_Observer $observer)
    {
        if($this->isCartEnabled()) {
            try{
                if ($hasChanges = $observer->getCart()->hasDataChanges()) {
                    $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getCart()->getQuote(),$this->_email,'updateItemInCart');
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function removeItemFromCart(Varien_Event_Observer $observer)
    {
        if($this->isCartEnabled()) {
            try{
                 Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(),$this->_email,'removeItemFromCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function placeOrder(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        if($this->isEnabled && $email = $order->getCustomerEmail()) {
            try{
                Mage::getModel('sailthruemail/client_purchase')->sendOrder($order);
                if (!$order->getCustomerIsGuest()) {
                    $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    Mage::getModel('sailthruemail/client_user')->updateCustomer($customer);

                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }


}
