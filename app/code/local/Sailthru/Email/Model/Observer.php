<?php

class Sailthru_Email_Model_Observer
{
    public function addNewCustomer(Varien_Event_Observer $observer)
    {
        $sailthru = Mage::helper('sailthruemail')->newSailthruClient();
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();
        $customerId = $customer->getid();
        $name = $customer->getName();
        $firstName = $customer->getFirstname();
        $lastName = $customer->getLastname();
        //$newsletter = Mage::getModel('newsletter/subscriber')->isSubscribed() ? '1' : '0';

        //prepare data to push to Sailthru
        $data = array();
        $data['id'] = $email;
        $data['key'] = 'email';
        $data['vars'] = array('customer_id' => $customerId,
                              'name' => $name,
                              'firstName' => $firstName,
                              'lastName' => $lastName,
                              //'newsletter' => $newsletter,
                             );
        $data['lists'] = array(Mage::helper('sailthruemail')->getMasterList());
        
        //Make API call to Sailthru to create new user
        try{    
            $response = $sailthru->apiPost('user', $data);
        } catch (Exception $e){
            Mage::logException($e);
            return false;
        }
        
        //uncomment line below to debug response from Sailthru API call. 
        $this->showData($data['lists']);
  /*
   *    Other Data that we may push 
   * 
        $name = $customer->getName();
        $firstName = $customer->getFirstname();
        $middleName = $customer->getMiddlename();
        $lastName = $customer->getLastname();
        $address = $customer->getAddresses();
        $attributes = $customer->getAttributes();
        $primaryBillingAddress = $customer->getDefaultBillingAddress();
        $primaryShippingAddress = $customer->getDefaultShippingAddress();
        $additionalAddress = $customer->getAdditionalAddresses();
        $zipCode = $customer->getPostcodoe();
        $groupId = $customer->getGroupId();
        $taxClassId = $customer->getTaxClassId();
        
        
        //store information
        $store_id = $customer->getStoreId();
        
     */   
    }
    
    
    public function updateUserProfile(Varien_Event_Observer $observer)
    {
        
    }
    /*
     * Use Sailthru's purchase api to push information to your account.
     *
     */
    public function pushIncompletePurchaseOrderToSailthru(Varien_Event_Observer $observer)
    {
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        if(!$email){
            //Do nothing if user is not logged in.
            return;
        }
        
        $sailthru = Mage::helper('sailthruemail')->newSailthruClient();
        try{//Schedule Abandoned Cart Email if that option has been enabled.
            if (Mage::helper('sailthruemail')->sendAbandonedCartEmails()){
                $template_name = "magento-abandoned-cart-template";  //Hardcoded for now. Should be changed later.
                $data = array("email" => $email, "incomplete" => 1, "items" => $this->shoppingCart(), "reminder_time" => "+".Mage::helper('sailthruemail')->getAbandonedCartReminderTime()." min", "reminder_template" => $template_name);
                $response = $sailthru->apiPost("purchase", $data);
                //uncomment line below to debug API call.
                //$this->showData($response);

                if($response["error"] == 14) {
                    $content = Mage::helper('sailthruemail')->createAbandonedCartHtmlContent();
                    $new_template = array("template" => $template_name, "content_html" => $content, "subject" => "Did you forget these items in your cart?", "from_email" => Mage::helper('sailthruemail')->getSenderEmail());
                    $create_template = $sailthru->apiPost('template', $new_template);
                    //$this->showData($create_template);
                    $response = $sailthru->apiPost("purchase", $data);
                    //$this->showData($response);
                }
            } else {
                $data = array("email" => $email, "incomplete" => 1, "items" => $this->shoppingCart());
                $response = $sailthru->apiPost("purchase", $data);
            }
         }
         catch (Exception $e) {
                Mage::logException($e);
                return false;
         }

    }


    public function pushPurchaseOrderSuccessToSailthru(Varien_Event_Observer $observer)
    {
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        if(!$email){
            //Do nothing if user is not logged in.
            return;
        }
        
        $sailthru = Mage::helper('sailthruemail')->newSailthruClient();
        try{//Schedule Abandoned Cart Email if that option has been enabled.
            $data = array("email" => $email, "items" => $this->shoppingCart());
            $response = $sailthru->apiPost("purchase", $data);
            //$this->showData($response);
        }catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        
        return;

    }

    private function shoppingCart()
    {
        $shopping_cart = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
        $items_in_cart = array();
        $i = 0;
 
            foreach($shopping_cart as $basket) {
                $product_id = $basket->getProductId();
                $product = Mage::getModel('catalog/product')->load($product_id);
                $quantity = $basket->getQty();
                $items_in_cart[$i] = array( 'qty' => $quantity,
                                            'title' => $product->getName(),
                                            'price' => $product->getPrice()*100,
                                            'id' => $product->getSku(),
                                            'url' => $product->getProductUrl(),
                                            'tags' => $product->getMetaKeyword(),
                                            'vars' => array(
                                                            //You can add more vars below
                                                            'id' => $product_id,
                                                            'image_url' => $product->getImageUrl(),
                                                            'currency' =>Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(),
                                                            'description' => $product->getDescription()
                                                           )

                                            );
                $i++;
            }
            
            return $items_in_cart;
    }

    private function showData($object)
    {
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }


}
