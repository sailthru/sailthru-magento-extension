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
                $data = array("email" => $email, "items" => $this->_getCartItems($quote));

                if (isset($_COOKIE['sailthru_bid'])){
                    $data['message_id'] = $_COOKIE['sailthru_bid'];
                }

                $data['incomplete'] = 1;
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
    public function sendOrder(Mage_Sales_Model_Quote $quote, $customer)
    {
        try{
            $data = array("email" => $customer->getEmail(), "items" => $this->_getCartItems($quote));
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

    /**
     * Prepare data on items in cart.
     *
     * @return type
     */
    protected function _getCartItems(Mage_Sales_Model_Quote $quote)
    {
        try {
            $quoteItems = $quote->getAllItems();
            $cartItems = array();

            foreach($quoteItems as $item) {
                if ($item->getProductType() != 'configurable') {

                    /**
                     * If variant, use paretn item info
                     */
                    if ($parentItem = $item->getParentItem()) {
                        $url = $parentItem->getProduct()->getProductUrl();
                        $price = $parentItem->getPrice();
                    } else {
                        $url = $item->getProduct()->getProductUrl();
                        $price = $item->getPrice();
                    }

                    $cartItems[] = array(
                        'qty' => intval($item->getQty()),
                        'title' => $item->getName(),
                        'price' => intval($price*100),
                        'id' => $item->getSku(),
                        'url' => $url,
                        'tags' => '',
                        'vars' => '',
                    );
                }
            }
            return $cartItems;
        } catch (Exception $e) {
             Mage::logException($e);
            return false;
        }
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