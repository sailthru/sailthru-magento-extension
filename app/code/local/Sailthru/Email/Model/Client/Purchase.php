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

            if ($email) {
                $data = array(
                        'email' => $email,
                        'items' => $this->_getItems($quote->getAllVisibleItems()),
                        'message_id' => Mage::getSingleton('core/cookie')->get('sailthru_bid'),
                        'incomplete' => 1
                        );
                $response = $this->apiPost('purchase', $data);
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     */
    public function sendOrder(Mage_Sales_Model_Order $order, $customer)
    {
        try{
            $this->_eventType = 'placeOrder';
            $data = array(
                    'email' => $customer->getEmail(),
                    'items' => $this->_getItems($order->getAllVisibleItems()),
                    'adjustments' => $this->_getAdjustments($order),
                    'message_id' => Mage::getSingleton('core/cookie')->get('sailthru_bid'),
                    'send_template' => 'purchase receipt',
                    'date' => $order->getCreatedAt() . ' ' . Mage::app()->getLocale()->getTimeZone(),
                    'tenders' => $this->_getTenders($order)
                    );
            $responsePurchase = $this->apiPost('purchase', $data);
            $responseUser = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);
        }catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Prepare data on items in cart or order.
     *
     * @return type
     */
    protected function _getItems($items)
    {
        try {
            $data = array();
            foreach($items as $item) {
                $_item = array();

                if ($item->getProductType() == 'configurable') {
                    $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                    $_item['title'] = $options['simple_name'];
                    $_item['id'] = $options['simple_sku'];
                    if ($vars = $this->_getVars($options)) {
                        $_item['vars'] = $vars;
                    }
                } else {
                    $_item['id'] = $item->getSku();

                    $_item['title'] = $item->getName();
                }

                if (get_class($item) == 'Mage_Sales_Model_Order_Item' ) {
                    $_item['qty'] = intval($item->getQtyOrdered());
                } else {
                    $_item['qty'] = intval($item->getQty());
                }

                $_item['url'] = $item->getProduct()->getProductUrl();
                $_item['price'] = Mage::helper('sailthruemail')->formatAmount($item->getProduct()->getPrice());

                if ($tags = $this->_getTags($item->getProductId())) {
                    $_item['tags'] = $tags;
                }

                $data[] = $_item;
            }
            return $data;
        } catch (Exception $e) {
             Mage::logException($e);
            return false;
        }
    }

    /**
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getAdjustments(Mage_Sales_Model_Order $order)
    {
        if ($order = $order->getBaseDiscountAmount()) {
            return array(
                   'title' => 'Sale',
                   'price' => Mage::helper('sailthruemail')->formatAmount($payment->getBaseDiscountAmount())
                   );
        }

        return array();
    }

    /**
     * Get payment information
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getTenders(Mage_Sales_Model_Order $order)
    {
        $tenders = array();

        if ($payment = $order->getPayment()) {
           $tenders[] = array(
                   'title' => $payment->getCcType(),
                   'price' => Mage::helper('sailthruemail')->formatAmount($payment->getBaseAmountOrdered())
                    );
        }

        return $tenders;
    }
    /**
     * Get product meta keywords
     * @param string $productId
     * @return string
     */
    protected function _getTags($productId)
    {
        return Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'meta_keyword', $this->_storeId);
    }

    /**
     *
     * @param array $options
     * @return array
     */
    protected function _getVars($options)
    {
        $vars = array();

        if (array_key_exists('attributes_info', $options)) {
            foreach($options['attributes_info'] as $attribute) {
                $vars[] = array($attribute['label'] => $attribute['value']);
            }
        }

        return $vars;
    }
}