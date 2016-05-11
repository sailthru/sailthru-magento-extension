<?php
/**
 * Purchase API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Purchase extends Sailthru_Email_Model_Abstract
{

    public function emptyCart(Varien_Event_Observer $observer)
    {
        $quote = $observer->getCart()->getQuote();
        $num_prods = $quote->getItemsCount();
        $num_qty = $quote->getItemsQty();
        if($quote->getItemsCount() == 0 && $this->_isEnabled && $this->_email) {
            try{
                 $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($quote,$this->_email,'EmptiedCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function addItemToCart(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_email && Mage::getStoreConfig('sailthru/email/abandoned_cart')) {
            try{
                $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(),$this->_email,'addItemToCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return $this;
        }
    }

    public function updateItemInCart(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_email && Mage::getStoreConfig('sailthru/email/abandoned_cart')) {
            try{
                if ($hasChanges = $observer->getCart()->hasDataChanges()) {
                    $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getCart()->getQuote(),$this->_email,'updateItemInCart');
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return $this;
        }
    }

    public function removeItemFromCart(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled && $this->_email && Mage::getStoreConfig('sailthru/email/abandoned_cart')) {
            try{
                 $response = Mage::getModel('sailthruemail/client_purchase')->sendCart($observer->getQuoteItem()->getQuote(),$this->_email,'removeItemFromCart');
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return $this;
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
        if($this->_isEnabled && $observer->getOrder()->getCustomerEmail()) {
            try{
                $response = Mage::getModel('sailthruemail/client_purchase')->sendOrder($observer->getOrder());
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }


}
