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

            if (!$email) {
                $email = $quote->getCustomerEmail() ?: $this->useHid();
                if(!$email) {
                    return false;
                }
            }

            // prevent bundle parts from surfacing
            $items = $quote->getAllVisibleItems();
            foreach ($items as $index => $item) {
                if ($item->getParentItem()){
                    unset($items[$index]);
                }
            }

            $data = array(
                    'email' => $email,
                    'items' => $this->_getItems($items),
                    'incomplete' => 1,
                    'reminder_time' => '+' . Mage::helper('sailthruemail')->getReminderTime() . ' min',
                    'reminder_template' => Mage::getStoreConfig('sailthru/email/abandoned_cart_template', $quote->getStoreId()),
                    'message_id' => $this->getMessageId()
            );

            $response = $this->apiPost('purchase', $data);

            if (array_key_exists('error',$response)){
                return $this->handleError($response, $quote, $email);
            }
            
            Mage::getSingleton('checkout/session')->setSailthuAbandonedCartId($quote->getId());
            
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Route errors
     *
     * @param array $response
     * @param Mage_Sales_Model_Quote $quote
     * @param string $email
     * @return boolean
     *
     * @todo For future iterations, use switch statement to handle multiple error messages.
     */
    public function handleError($response, $quote, $email)
    {
            if($response['error'] == 14) {
                /**
                 * Response Error 14 means that an unknown template was passed in the API call.
                 * This normally happens for first time API calls or when the name of the template has
                 * been changed, http://getstarted.sailthru.com/api/api-response-errors.  We'll
                 * therefore need to create a template to pass in the call.  One condition for the
                 * template to be created is that the sender email must be verified so please check
                 * https://my.sailthru.com/verify to make sure that the send email is listed there.
                 */
                return $this->createAbandonedCartEmail($quote, $email);
            } else {
                Mage::throwException('Unknown purchase response error: ' . json_encode($response));
            }

    }

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     */
    public function sendOrder(Mage_Sales_Model_Order $order)
    {
        try{
            $this->_eventType = 'placeOrder';

            $data = [
                    'email' => $order->getCustomerEmail(),
                    'items' => $this->_getItems($order->getAllVisibleItems()),
                    'adjustments' => $this->_getAdjustments($order),
                    'message_id' => $this->getMessageId(),
                    'send_template' => 'Purchase Receipt',
                    'tenders' => $this->_getTenders($order),
                    'purchase_keys' => ["extid" => $order->getIncrementId()]
            ];
            /**
             * Send order data to purchase API
             */
            $responsePurchase = $this->apiPost('purchase', $data);

            /**
             * Send customer data to user API
             */
            //$responseUser = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);
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
                    $_item['vars'] = $this->_getVars($options);
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
                    
                    if ($tags = $this->_getTags($item->getProductId())) {
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
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getAdjustments(Mage_Sales_Model_Order $order)
    {
        if ($order->getBaseDiscountAmount()) {
            return array(
                array(
                    'title' => 'Sale',
                    'price' => Mage::helper('sailthruemail')->formatAmount($order->getBaseDiscountAmount())
                )
            );
        }

        return array();
    }

    /**
     * Get payment information
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getTenders(Mage_Sales_Model_Order $order)
    {

        if ($order->getPayment()) {
           $tenders = array(
                        array(
                          'title' => $order->getPayment()->getCcType(),
                          'price' => Mage::helper('sailthruemail')->formatAmount($order->getPayment()->getBaseAmountOrdered())
                           )
                       );
            if ($tenders[0]['title'] == null) {
                return '';
            }
            return $tenders;
        } else {
            return '';
        }
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

    /**
     * Create Abandoned Cart Email
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $email
     * @return boolean
     */
    public function createAbandonedCartEmail($quote,$email)
    {
        try{
            $this->_eventType = 'abandonedCart';

            $storeId = $quote->getStoreId();
            $reminderTemplate = Mage::getStoreConfig('sailthru/email/abandoned_cart_template', $storeId);

            $newTemplate = array(
                           'template' => $reminderTemplate,
                           'content_html' => $this->_getContent(),
                           'subject' => Mage::getStoreConfig('sailthru/email/abandoned_subject', $storeId),
                           'from_name' => Mage::getStoreConfig('sailthru/email/abandoned_cart_sender_name', $storeId),
                           'from_email' => Mage::getStoreConfig('sailthru/email/abandoned_cart_sender_email', $storeId),
                           'is_link_tracking' => 1,
                           'is_google_analytics' => 1
                            );

            $templateResponse = $this->apiPost('template', $newTemplate);

            //Send Purchase Data
            $data = array(
                    'email' => $email,
                    'incomplete' => 1,
                    'items' => $this->_getItems($quote->getAllVisibleItems()),
                    'reminder_template' => $reminderTemplate
                    );
            $response = $this->apiPost("purchase", $data);
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Get email from sailthru cookie.
     * TODO: Add seperate config to check for anonymous abandon.
     *
     * @return string|bool
     */
    private function useHid(){
        $this->log('trying to use Hid');
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
        return false;
    }

    /**
     * Get Abandoned cart email content formatted in Zephyr
     *
     * It's important to note that the code below only works if routed through Sailthru.
     *
     * Include css or tables here to style e-mail.
     *
     * It's important that the "from_email" is verified, otherwise the code below will not work.
     *
     * @return string
     */
    private function _getContent()
    {
        return '{*Sailthru zephyr code is used for full functionality*}
            <div id="main">
                <table width="700">
                    <tr>
                        <td>
                            <h2><p>Hello {profile.vars.name}</p></h2>
                            <p>Did you forget the following items in your cart?</p>
                            <table>
                                <thead>
                                    <tr>
                                        <td colspan="2">
                                            <div><span style="display:block;text-align:center;color:white;font-size:13px;font-weight:bold;padding:15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{profile.purchase_incomplete.items[0].vars.checkout_url}">Re-Order Now!</a></span></div>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                {sum = 0}
                                {foreach profile.purchase_incomplete.items as i}
                                    <table width="650" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px 0;background:#fff;border:1px solid #e5e5e5">
                                        <tbody>
                                            <tr>
                                                <td style="padding:20px"><a href="{i.url}"><img width="180" height="135" border="0" alt="{i.title}" src="{i.vars.image_url}"></a></td>
                                                <td width="420" valign="top" style="padding:20px 10px 20px 0">
                                                    <div style="padding:5px 0;color:#333;font-size:18px;font-weight:bold;line-height:21px">{i.title}</div>
                                                    <div style="padding:0 0 5px 0;color:#999;line-height:21px;margin:0px">{i.vars.currency}{i.price/100}</div>
                                                    <div style="color:#999;font-weight:bold;line-height:21px;margin:0px">{i.description}</div>
                                                    <div><span style="display:block;text-align:center;width:120px;border-left:1px solid #b43e2e;border-right:1px solid #b43e2e;color:white;font-size:13px;font-weight:bold;padding:0 15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{i.url}">Buy Now</a></span></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                {/foreach}
                                <tr>
                                    <td align="left" valign="top" style="padding:3px 9px" colspan="2"></td>
                                    <td align="right" valign="top" style="padding:3px 9px"></td>
                                </tr>
                                </tbody>
                                <tfoot>
                                </tfoot>
                            </table>
                            <p><small>If you believe this has been sent to you in error, please safely <a href="{optout_confirm_url}">unsubscribe</a>.</small></p>
                            {beacon}
                        </td>
                    </tr>
                </table>
            </div>';
    }

}
