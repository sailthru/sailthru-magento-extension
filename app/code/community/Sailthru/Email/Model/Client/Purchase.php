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
     * @return void
     * @throws Sailthru_Client_Exception
     */
    public function sendCart(Mage_Sales_Model_Quote $quote, $eventType = null)
    {
        if ($eventType){
            $this->_eventType = $eventType;
        }

        $email = $quote->getCustomerEmail();
        if (Mage::helper('sailthruemail')->isAbandonedCartEnabled() and $email){
            $cartTime = Mage::helper('sailthruemail')->getAbandonedCartDelayTime();
            $cartTemplate = Mage::helper('sailthruemail')->getAbandonedCartTemplate();
        } elseif (Mage::helper('sailthruemail')->isAnonymousCartEnabled() and $email = $this->useHid()){
            $cartTemplate = Mage::helper('sailthruemail')->getAnonymousCartTemplate();
            $cartTime = Mage::helper('sailthruemail')->getAnonymousCartDelayTime();
        } else {
            return;
        }

        // prevent bundle parts from surfacing
        /** @var $items Mage_Sales_Model_Quote_Item[] */
        $items = $quote->getAllVisibleItems();
        foreach ($items as $index => $item) {
            if ($item->getParentItem()){
                unset($items[$index]);
            }
        }

        $data = array(
                'email' => $email,
                'items' => $this->getItems($items),
                'incomplete' => 1,
                'reminder_time' => '+' . $cartTime,
                'reminder_template' => $cartTemplate,
                'message_id' => $this->getMessageId()
        );

        $this->apiPost('purchase', $data);
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param $order Mage_Sales_Model_Order
     * @return void
     * @throws Sailthru_Client_Exception
     */ 
    public function sendOrder(Mage_Sales_Model_Order $order)
    {
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        $method = $quote->getCheckoutMethod(true);
        if ($method == 'register') {
            try { // main goal is purchase, so continue is user api fails
                Mage::getModel('sailthruemail/client_user')->postNewCustomer($order->getCustomer());
            } catch (Sailthru_Client_Exception $e) {
                Mage::logException($e);
            }
        }

        $data = array(
            'email' => $order->getCustomerEmail(),
            'items' => $this->getItems($order->getAllVisibleItems()),
            'adjustments' => Mage::helper('sailthruemail/purchase')->getAdjustments($order, "api"),
            'message_id' => $this->getMessageId(),
            'tenders' => Mage::helper('sailthruemail/purchase')->getTenders($order),
            'purchase_keys' => array("extid" => $order->getIncrementId())
        );

        $this->apiPost('purchase', $data);
    }

    /**
     * Prepare data on items in cart or order.
     * @param $items Mage_Sales_Model_order_Item[]|Mage_Sales_Model_Quote_Item[]
     * @return array|bool
     */
    public function getItems($items)
    {
        try {
            $data = array();
            foreach($items as $item) {
                $_item = array();
                $_item['vars'] = array();
                $product = $item->getProduct();
                $_item['id'] = $item->getSku();
                $_item['title'] = $item->getName();
                if ($_item['id']) {
                    if ($item instanceof Mage_Sales_Model_Order_Item) {
                        $_item['qty'] = intval($item->getQtyOrdered());
                    } else {
                        $_item['qty'] = intval($item->getQty());
                    }
                    $_item['vars'] = Mage::helper('sailthruemail/purchase')->getItemVars($item);
                    $_item['url'] = $item->getProduct()->getProductUrl();
                    $_item['price'] = Mage::helper('sailthruemail')->formatAmount($item->getProduct()->getFinalPrice());
                    $_item['vars']['image'] = Mage::helper('sailthruemail')->getProductImages($product);
                    if ($tags = Mage::helper('sailthruemail')->getTags($item->getProduct())) {
                        $_item['tags'] = $tags;
                    }

                    $data[] = $_item;
                }
            }

            return $data;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Get email from sailthru cookie.
     *
     * @return string|bool
     */
    private function useHid()
    {
        if (!Mage::helper('sailthruemail')->isAnonymousCartEnabled()) {
            return false;
        }

        $cookie = Mage::getModel('core/cookie')->get('sailthru_hid');
        if ($cookie) {
            try {
                $response = $this->getUserByKey($cookie, 'cookie', array('keys' => 1));
                if (array_key_exists('keys', $response)){
                    return $response['keys']['email'];
                }
            } catch (Exception $e) {
                $this->logException($e);
            }
        }
        return false;
    }

}
