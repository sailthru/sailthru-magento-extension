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
     * Prepare data on items in cart.
     *
     * @return type
     */
    protected function _shoppingCart()
    {
        try {
            $shopping_cart = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
            $items_in_cart = array();
            $i = 0;

            foreach($shopping_cart as $basket) {
                $product_id = $basket->getProductId();
                $product = Mage::getModel('catalog/product')->load($product_id);

                if ($product->isSuper()) continue;
                    $this->_getSailthruClient()->log(array(
                        'isSuperGroup' => $product->isSuperGroup(),
                        'isGrouped'   => $product->isGrouped(),
                        'isConfigurable'  => $product->isConfigurable(),
                        'isSuper' => $product->isSuper(),
                        'isSalable' => $product->isSalable(),
                        'isAvailable'  => $product->isAvailable(),
                        'isVirtual'  => $product->isVirtual(),
                        'id' => $product->getSku(),
                    ),"PRODUCT");


                //Use Configurable Product URL for Simple Products
                $parentID = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild( $product->getId() );
                if (isset($parentID[0])){
                    $parentProduct = Mage::getModel('catalog/product')->load($parentID[0]);
                    $productUrl = $parentProduct->getProductUrl();
                }else{
                    $productUrl = $product->getProductUrl();
                }

                $quantity = $basket->getQty();
                $items_in_cart[$i] = array( 'qty' => $quantity,
                    'title' => $product->getName(),
                    'price' => $product->getPrice()*100,
                    'id' => $product->getSku(),
                    'url' => $productUrl,
                    'tags' => $product->getMetaKeyword(),
                    'vars' => $this->getProductData($product),
                );
                $i++;
            }

            return $items_in_cart;
        } catch (Exception $e) {
             Mage::logException($e);
            return false;
        }
    }

    public function sendAbandonedCart($cart,$email)
    {
        try{
            $data = array("email" => $email,
                "incomplete" => 1,
                "items" => $this->_getItems($cart),
                "reminder_time" => "+".Mage::helper('sailthruemail')->getAbandonedCartReminderTime()." min",
                "reminder_template" => Mage::helper('sailthruemail')->getAbandonedCartTemplate(),
                "message_id" => isset($_COOKIE['sailthru_bid']) ? $_COOKIE['sailthru_bid'] : null
            );

            $response = $this->apiPost('purchase', $data);

            //For future iterations, use switch statement to handle multiple error messages.
            if($response['error'] == 14) {
                /**
                 * Response Error 14 means that an unknown template was passed in the API call.
                 * This normally happens for first time API calls or when the name of the template has
                 * been changed, http://getstarted.sailthru.com/api/api-response-errors.  We'll
                 * therefore need to create a template to pass in the call.  One condition for the
                 * template to be created is that the sender email must be verified so please check
                 * https://my.sailthru.com/verify to make sure that the send email is listed there.
                 */
                //Create Abandoned Cart Email
                $content = Mage::helper('sailthruemail')->createAbandonedCartHtmlContent();
                $new_template = array("template" => Mage::helper('sailthruemail')->getAbandonedCartTemplate(),
                        'content_html' => Mage::helper('sailthruemail')->createAbandonedCartHtmlContent(),
                        'subject' => Mage::helper('sailthruemail')->getAbandonedCartSubject(),
                        'from_name' => Mage::helper('sailthruemail')->getAbandonedCartSenderName(),
                        'from_email' => Mage::helper('sailthruemail')->getAbandonedCartSenderEmail(),
                        'is_link_tracking' => 1,
                        'is_google_analytics' => 1
                );
                $create_template = $this->apiPost('template', $new_template);
                //Mage::log($create_template);

                //Send Purchase Data
                $response = $this->apiPost("purchase", $data);
                //Mage::log($response);
            } else {
                $data = array("email" => $email, "incomplete" => 1, "items" => $this->shoppingCart());
                $response = $this->apiPost("purchase", $data);
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
    public function sendOrderSuccess($cart, $email, $customer)
    {
        try{
            $data = array("email" => $email, "items" => $this->shoppingCart($cart));
            if (isset($_COOKIE['sailthru_bid'])){
                $data['message_id'] = $_COOKIE['sailthru_bid'];
            }

            $response1 = $this->apiPost("purchase", $data);
            $response2 = Mage::getModel('sailthruemail/client_user')->sendCustomerData($customer);

        }catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

    }
}