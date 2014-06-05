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
                        'message_id' => Mage::getSingleton('core/cookie')->getSailthruBid(),
                        'incomplete' => 1
                        );

                $response = $this->apiPost("purchase", $data);
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
                    'message_id' => Mage::getSingleton('core/cookie')->getSailthruBid(),
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
                if ($item->getProductType() != 'configurable') {
                    /**
                     * If variant, use parent item info
                     */
                    if ($parentItem = $item->getParentItem()) {
                        $url = $parentItem->getProduct()->getProductUrl();
                        $price = $parentItem->getPrice();
                    } else {
                        $url = $item->getProduct()->getProductUrl();
                        $price = $item->getPrice();
                    }

                    $data[] = array(
                        'qty' => intval($item->getQty()),
                        'title' => $item->getName(),
                        'price' => Mage::helper('sailthruemail')->formatAmount($price),
                        'id' => $item->getSku(),
                        'url' => $url,
                        'tags' => '',
                        'vars' => '',
                    );
                }
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
        if ($payment = $order->getPayment()) {
            return array(
                'title' => $payment->getCcType(),
                'price' => Mage::helper('sailthruemail')->formatAmount($payment->getBaseAmountOrdered())
            );
        }

        return array();
    }


    /**
     * Get product meta keywords
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getTags(Mage_Catalog_Model_Product $product)
    {
        return '';
    }

    /**
     * Get product attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _getVars(Mage_Catalog_Model_Product $product)
    {
        return array();
    }
}