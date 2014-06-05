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
    protected function _getCartItems(Mage_Sales_Model_Quote $quote)
    {
        try {
            $quoteItems = $quote->getAllItems();
            $cartItems = array();

            foreach($quoteItems as $item) {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                $cartItems[] = array(
                    'qty' => intval($item->getQty()),
                    'title' => $item->getName(),
                    'price' => intval($item->getPrice()*100),
                    'id' => $item->getSku(),
                    'url' => $this->_getUrl($product),
                    'tags' => '',//$product->_getTags(),
                    'vars' => ''//$this->getVars($product),
                );
            }
            return $cartItems;
        } catch (Exception $e) {
             Mage::logException($e);
            return false;
        }
    }

    /**
     * Get product URL
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getUrl(Mage_Catalog_Model_Product $product)
    {
        //Use Configurable Product URL for Simple Products
        if (($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) &&
            ($parentId = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId()))) {
            $parentProduct = Mage::getModel('catalog/product')->load($parentId[0]);
            return $parentProduct->getProductUrl();
        } else {
            return $product->getProductUrl();
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

    public function sendCart(Mage_Sales_Model_Quote $quote, $email)
    {
        try{
            $data = array("email" => $email, "items" => $this->_getCartItems($quote));

            if (isset($_COOKIE['sailthru_bid'])){
                $data['message_id'] = $_COOKIE['sailthru_bid'];
            }

            $data['incomplete'] = 1;
            $response = $this->apiPost("purchase", $data);

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
}