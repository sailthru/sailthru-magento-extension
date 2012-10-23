<?php
/**
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */
class Sailthru_Email_Model_Observer
{
    protected function _getSailthruClient() {
        return Mage::helper('sailthruemail')->newSailthruClient();
    }

    protected function _debug($object) {
        return Mage::helper('sailthruemail')->debug($object);
    }

    public function updateCustomer($observer) {
        $customer = $observer->getEvent()->getCustomer();
        //$customer = Mage::getModel('customer/session')->getCustomer();
        if (($customer instanceof Mage_Customer_Model_Customer)) {
            try{
                $sailthru = $this->_getSailthruClient();
                $response = $sailthru->saveUser($customer->getId(), $this->getCustomerData($customer));
                //$this->_debug($response);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function createNewCustomer($observer) {
        //$this->_debug($observer);
        $customer = $observer->getEvent()->getCustomer();
        //$this->_debug($customer);
        if (($customer instanceof Mage_Customer_Model_Customer)) {
            try{
                $sailthru = $this->_getSailthruClient();
                $options = $this->getCustomerData($customer);

                //$this->_debug($options);
                $response = $sailthru->createNewUser($this->getCustomerData($customer));
                //$this->_debug($response);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * Push new subscriber data to Sailthru
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function addSubscriber(Varien_Event_Observer $observer)
    {
        //$this->_debug($observer);
        //Sync subscriber data with Sailthru
        $subscriber = $observer->getEvent()->getSubscriber();
        //$this->_debug($subscriber);

        //Make API call to Sailthru to add subscriber
        try{
            $sailthru = $this->_getSailthruClient();

            $email = $subscriber->getEmail();
            $vars = array(
                'subscriberId' => $subscriber->getId(),
                'status' => $subscriber->getSubscriberStatus(),
                'fullName' => $subscriber->getSubscriberFullName()
            );
            $lists = array('Newsletter Subscribers' => 1);      //hardcoded for now
            $response = $sailthru->setEmail($email, $vars, $lists);
            //$this->_debug($response);
        } catch (Exception $e){
            Mage::logException($e);
            return false;
        }

        return $this;

    }



    public function log($observer) {
        $event = $observer->getEvent();
        if (Mage::helper('sailthruemail')->isTransactionalEmailEnabled()) {
            //code to enable logging from Sailthru here.
        }
    }

    public function getCustomerData($customer) {
        $options = array(
            'id' => $customer->getEmail(),
            'key' => 'email',
            'vars' => array(
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'suffix' => $customer->getSuffix(),
                'prefix' => $customer->getPrefix(),
                'firstName' => $customer->getFirstName(),
                'middleName' => $customer->getMiddlename(),
                'lastName' => $customer->getLastName(),
                'address' => $customer->getAddresses(),
                /*'attributes' => $customer->getAttributes(),
                'storeID' => $customer->getStoreId(),
                'websiteId' => $customer->_getWebsiteStoreId(),
                'groupId' => $customer->getGroupId(),
                'taxClassId' => $customer->getTaxClassId(),
                'createdAt' => $customer->getCreatedAtTimestamp(),
                'primaryBillingAddress' => $customer->getPrimaryBillingAddress(),
                'defaultBillingAddress' => $customer->getDefaultBillingAddress(),
                'defaultShippingAddress' => $customer->getDefaultShippingAddress(),
                'regionId' => $customer->getRegionId(),
                'zipCode' => $customer->getPostCode(),
                 * *
                 */
            ),
            'lists' => array(
                'Master List' => 1,  //Hard coded for now
            ),
        );

        return $options;

    }





    /**
     * Customer delete handler
     *
     * @param Varien_Object $observer
     * @return Mage_Newsletter_Model_Observer
     */
    public function deleteCustomer($observer)
    {
        $subscriber = Mage::getModel('newsletter/subscriber')
            ->loadByEmail($observer->getEvent()->getCustomer()->getEmail());
        if($subscriber->getId()) {
            $subscriber->delete();
        }
        return $this;
    }

    /**
     * Push new customer information to Sailthru
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function addCustomer(Varien_Event_Observer $observer)
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
        //$this->_debug($response);
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

    /**
     * Push items that have been added to Cart to Sailthru
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function pushIncompletePurchaseOrderToSailthru(Varien_Event_Observer $observer)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        if(!$email){
            //Do nothing if user is not logged in.
            return;
        }

        $sailthru = Mage::helper('sailthruemail')->newSailthruClient();
        try{//Schedule Abandoned Cart Email if that option has been enabled.
            $this->updateCustomer($observer);
            if (Mage::helper('sailthruemail')->sendAbandonedCartEmails()){
                $template_name = "magento-abandoned-cart-template";  //Hardcoded for now. Should be changed later.
                $data = array("email" => $email, "incomplete" => 1, "items" => $this->shoppingCart(), "reminder_time" => "+".Mage::helper('sailthruemail')->getAbandonedCartReminderTime()." min", "reminder_template" => $template_name);
                $data['message_id'] = isset($_COOKIE['sailthru_bid']) ? $_COOKIE['sailthru_bid'] : null;
                $response = $sailthru->apiPost("purchase", $data);
                //uncomment line below to debug API call.
                //$this->_debug($response);

                if($response["error"] == 14) {
                    $content = Mage::helper('sailthruemail')->createAbandonedCartHtmlContent();
                    $new_template = array("template" => $template_name, "content_html" => $content, "subject" => "Did you forget these items in your cart?", "from_email" => Mage::helper('sailthruemail')->getSenderEmail());
                    $create_template = $sailthru->apiPost('template', $new_template);
                    //$this->_debug
                    //($create_template);
                    $response = $sailthru->apiPost("purchase", $data);
                    //$this->_debug($response);
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

    /**
     * Notify Sailthru that a purchase has been made. This automatically cancels
     * any scheduled abandoned cart email.
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function pushPurchaseOrderSuccessToSailthru(Varien_Event_Observer $observer)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        if(!$email){
            return;
        }

        $sailthru = Mage::helper('sailthruemail')->newSailthruClient();
        try{
            //$this->updateCustomer($customer);
            $data = array("email" => $email, "items" => $this->shoppingCart());
            if (isset($_COOKIE['sailthru_bid'])){
                $data['message_id'] = $_COOKIE['sailthru_bid'];
            }

            $response = $sailthru->apiPost("purchase", $data);
            //$this->_debug($response);
        }catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $this;

    }

    /**
     * Prepare data on items in cart.
     *
     * @return type
     */
    public function shoppingCart()
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
            //$this->_debug($items_in_cart);

            return $items_in_cart;
    }


}
