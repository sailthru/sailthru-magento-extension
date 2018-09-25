<?php
/**
 * Purchase API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Purchase extends Sailthru_Email_Model_Observer
{

    public function emptyCart(Varien_Event_Observer $observer)
    {
        $quote = $observer->getCart()->getQuote();
        if($quote->getItemsCount() == 0) {
            try{
                 $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($quote, 'EmptiedCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function addItemToCart(Varien_Event_Observer $observer)
    {
        try{
            $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(), 'addItemToCart');
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function updateItemInCart(Varien_Event_Observer $observer)
    {
        try{
            if ($hasChanges = $observer->getCart()->hasDataChanges()) {
                $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getCart()->getQuote(), 'updateItemInCart');
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function removeItemFromCart(Varien_Event_Observer $observer)
    {
        try{
             Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(), 'removeItemFromCart');
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function placeOrder(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        if($this->isEnabled && $email = $order->getCustomerEmail()) {
            $purchaseClient = Mage::getModel('sailthruemail/client_purchase');
            try {
                $purchaseClient->sendOrder($order);
            } catch (Exception $e) {
                Mage::logException($e);
            }

            try {
                $purchaseClient->setHorizonCookie($email);
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }

            if (!$order->getCustomerIsGuest()) {
                try {
                    $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    Mage::getModel('sailthruemail/client_user')->updateCustomer($customer);
                } catch (Sailthru_Client_Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }


}
