<?php
/**
 * Content API Observer Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer_Content extends Sailthru_Email_Model_Abstract
{

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        if($this->_isEnabled) {
            try{
                $product = $observer->getEvent()->getProduct();
                $response = Mage::getModel('sailthruemail/client_content')->deleteProduct($product);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function saveProduct(Varien_Event_Observer $observer)
    {
       if($this->_isEnabled) {
            $product = $observer->getEvent()->getProduct();
            $status = $product->getStatus();
            $sailthruContent = Mage::getModel('sailthruemail/client_content');
            try{
                if($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED){
                    $response = $sailthruContent->saveProduct($product);
                } elseif($status == Mage_Catalog_Model_Product_Status::STATUS_DISABLED){
                    $response = $sailthruContent->deleteProduct($product);
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

}