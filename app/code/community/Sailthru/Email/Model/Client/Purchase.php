<?php
/**
 * Client Purchase Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 *
 */
class Sailthru_Email_Model_Client_Purchase extends Sailthru_Email_Model_Client
{

    /**
     * Create Cart data to post to API
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $email
     * @param string $eventType
     * @return boolean
     */
    public function sendCart(Mage_Sales_Model_Quote $quote, $email = null, $eventType = null)
    {
        try{

            if ($eventType){
                $this->_eventType = $eventType;
            }

            $email = $quote->getCustomerEmail();
            if (Mage::helper('sailthruemail')->isAbandonedCartEnabled() and $email){
                $cartTime = Mage::helper('sailthruemail')->getAbandonedCartDelayTime();
                $cartTemplate = Mage::helper('sailthruemail')->getAbandonedCartTemplate();
            } elseif (Mage::helper('sailthruemail')->isAnonymousCartEnabled and $email = $this->useHid()){
                $cartTemplate = Mage::helper('sailthruemail')->getAnonymousCartTemplate();
                $cartTime = Mage::helper('sailthruemail')->getAnonymousCartDelayTime();
            } else {
                return false;
            }

            /** @var $items Mage_Sales_Model_Quote_Item[] */
            // prevent bundle parts from surfacing
            $items = $quote->getAllVisibleItems();
            foreach ($items as $index => $item) {
                if ($item->getParentItem()){
                    unset($items[$index]);
                }
            }

            $data = array(
                    'email' => $email,
                    'items' => Mage::helper('sailthruemail/purchase')->getItems($items),
                    'incomplete' => 1,
                    'reminder_time' => '+' . $cartTime,
                    'reminder_template' => $cartTemplate,
                    'message_id' => $this->getMessageId()
            );

            $response = $this->apiPost('purchase', $data);

            return true;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param $order Mage_Sales_Model_Order
     * @return void
     */ 
    public function sendOrder(Mage_Sales_Model_Order $order)
    {
        $this->_eventType = 'Place Order';
        $data = [
            'email' => $order->getCustomerEmail(),
            'items' => Mage::helper('sailthruemail/purchase')->getItems($order->getAllVisibleItems()),
            'adjustments' => Mage::helper('sailthruemail/purchase')->getAdjustments($order, "api"),
            'message_id' => $this->getMessageId(),
            'tenders' => Mage::helper('sailthruemail/purchase')->getTenders($order),
            'purchase_keys' => ["extid" => $order->getIncrementId()]
        ];
        try{
            $this->apiPost('purchase', $data);
        }catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get email from sailthru cookie.
     *
     * @return string|bool
     */
    private function useHid()
    {
        $this->log("checking for HID use");
        if (Mage::helper('sailthruemail')->isAnonymousCartEnabled()) {
            try {
                $cookie = Mage::getModel('core/cookie')->get('sailthru_hid');
                if ($cookie){
                    $response = $this->getUserByKey($cookie, 'cookie', ['keys' => 1]);
                    if (array_key_exists('keys', $response)){
                        return $response['keys']['email'];
                    }
                }
            } catch (Exception $e){
                $this->log($e);
            }
        }
        return false;
    }

}
