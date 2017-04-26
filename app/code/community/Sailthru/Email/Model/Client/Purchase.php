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
     * @throws Sailthru_Email_Model_Client_Exception
     */
    public function sendCart(Mage_Sales_Model_Quote $quote, $email = null, $eventType = null)
    {
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

        $response = $this->apiPost('purchase', $data);
    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param $order Mage_Sales_Model_Order
     * @return void
     * @throws Sailthru_Email_Model_Client_Exception
     */ 
    public function sendOrder(Mage_Sales_Model_Order $order)
    {
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        $method = $quote->getCheckoutMethod(true);
        if ($method == 'register'){
            Mage::getModel('sailthruemail/client_user')->postNewCustomer($order->getCustomer());
        }
        $data = [
            'email' => $order->getCustomerEmail(),
            'items' => $this->getItems($order->getAllVisibleItems()),
            'adjustments' => Mage::helper('sailthruemail/purchase')->getAdjustments($order, "api"),
            'message_id' => $this->getMessageId(),
            'tenders' => Mage::helper('sailthruemail/purchase')->getTenders($order),
            'purchase_keys' => ["extid" => $order->getIncrementId()]
        ];

        $response = $this->apiPost('purchase', $data);
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
            $configurableSkus = array();
            foreach($items as $item) {
                $_item = array();
                $_item['vars'] = array();
                $product = $item->getProduct();
                $productType = $item->getProductType();
                if ($productType == 'configurable') {
                    $parentIds[] = $item->getParentItemId();
                    $options = $product->getTypeInstance(true)->getOrderOptions($product);
                    $_item['id'] = $options['simple_sku'];
                    $_item['title'] = $options['simple_name'];
                    $_item['vars'] = $this->getVars($options);
                    $configurableSkus[] = $options['simple_sku'];
                } elseif (!in_array($item->getSku(),$configurableSkus)) {
                    $_item['id'] = $item->getSku();
                    $_item['title'] = $item->getName();
                } else {
                    $_item['id'] = null;
                }
                if ($_item['id']) {
                    if ($item instanceof Mage_Sales_Model_Order_Item ) {
                        $_item['qty'] = intval($item->getQtyOrdered());
                    } else {
                        $_item['qty'] = intval($item->getQty());
                    }
                    $_item['url'] = $item->getProduct()->getProductUrl();
                    $_item['price'] = Mage::helper('sailthruemail')->formatAmount($item->getProduct()->getFinalPrice());
                    // NOTE: Thumbnail comes from cache, so if cache is flushed the THUMBNAIL may be innacurate.
                    if (!isset($_item['vars']['image'])) {
                        $_item['vars']['image'] = [
                            "large"     => Mage::helper('catalog/product')->getImageUrl($product),
                            "small"     => Mage::helper('catalog/product')->getSmallImageUrl($product),
                            "thumbnail" => Mage::helper('catalog/image')->init($product, 'thumbnail')->__toString(),
                        ];
                    }
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
