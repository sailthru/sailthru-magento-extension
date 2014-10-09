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

    protected $_isAbandonedEnabled = false;

    protected $_isTransactionalsEnabled = false;
    
    
    public function __construct() {
        $this->_isAbandonedEnabled = Mage::helper('sailthruemail')->sendAbandonedCartEmails();
        $this->_isTransactionalsEnabled = Mage::helper('sailthruemail')->isTransactionalEmailEnabled;
        parent::__construct();
    }

    public function addItemToCart(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled) {
            return;
        }
        
        if($this->_isAbandonedEnabled && $this->_email && $this->_isTransactionalsEnabled) {
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
        if (!$this->_isEnabled) {
            return;
        }
        
        if($this->_isAbandonedEnabled && $this->_email && $this->_isTransactionalsEnabled) {
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
        if (!$this->_isEnabled) {
            return;
        }
        
        if($this->_isAbandonedEnabled && $this->_email && $this->_isTransactionalsEnabled) {
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
