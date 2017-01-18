<?php
/**
 * Abandoned Cart Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Purchase_Abandoned extends Sailthru_Email_Model_Observer_Purchase
{

    public function sendAbandonedCart(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($this->_isEnabled && $customer->getEmail()) {
            try{
                $cart = $observer->getCart();
                $response = Mage::getModel('sailthruemail/client_purchase_abandoned')->sendCart($cart,$customer->getEmail());
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }


}