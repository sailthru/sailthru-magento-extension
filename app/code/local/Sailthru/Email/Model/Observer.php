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

    /**
     * Push new subscriber data to Sailthru
     *
     * @param Varien_Event_Observer $observer
     * @return
     */
    public function subscriberHandler(Varien_Event_Observer $observer)
    {
        if(!Mage::helper('sailthruemail')->isEnabled()) {
             return;
         }

        $subscriber = $observer->getEvent()->getSubscriber();

        $data = array('id' => $subscriber->getSubscriberEmail(),
                      'key' => 'email',
                      'keys' => array('email' => $subscriber->getSubscriberEmail()),
                      'keysconflict' => 'merge',
                      'lists' => array(Mage::helper('sailthruemail')->getNewsletterList() => 1),  //this should be improved. Check to see if list is set.  Allow multiple lists.
                      'vars' => array('subscriberId' => $subscriber->getSubscriberId(),
                                    'status' => $subscriber->getSubscriberStatus(),
                                    'Website' => Mage::app()->getStore()->getWebsiteId(),
                                    'Store' => Mage::app()->getStore()->getName(),
                                    'Store Code' => Mage::app()->getStore()->getCode(),
                                    'Store Id' => Mage::app()->getStore()->getId(),
                                    'fullName' => $subscriber->getSubscriberFullName(),
                                    ),
                      'fields' => array('keys' => 1),
                      //Hacky way to prevent user from getting campaigns if they are not subscribed
                      //An example is when a user has to confirm email before before getting newsletters.
                      'optout_email' => ($subscriber->getSubscriberStatus() != 1) ? 'blast' : 'none',
                );

        //Make Email API call to sailthru to add subscriber
        try {
            $sailthru = $this->_getSailthruClient();
            $response = $sailthru->apiPost('user', $data);
            Mage::log($data);
            $sailthru_hid = $response['keys']['cookie'];
            $cookie = Mage::getSingleton('core/cookie')->set('sailthru_hid', $sailthru_hid);
        }catch(Exception $e) {
            Mage::logException($e);
        }

        return $this;

    }

    public function customerHandler($observer)
    {

        if(!Mage::helper('sailthruemail')->isEnabled()) {
             return;
         }
         Mage::log($observer);

        $customer = $observer->getEvent()->getCustomer();
          //$customer = Mage::getSingleton('customer/session')->getCustomer();
        Mage::log($customer);
            try{
                $sailthru = $this->_getSailthruClient();
                $data = array(''

                             );
                $response = $sailthru->apiPost('user', $this->getCustomerData($customer));
                //Mage::log($this->getCustomerData($customer));
                //Mage::log($response);

                $sailthru_hid = $response['keys']['cookie'];
                $cookie = Mage::getSingleton('core/cookie')->set('sailthru_hid', $sailthru_hid);
            } catch (Exception $e) {
                Mage::logException($e);
            }

        return $this;
    }

    public function getCustomerData($customer) {
        $data = array(
            'id' => $customer->getEmail(),
            'key' => 'email',
            'fields' => array('keys' => 1),
            'keysconfict' => 'merge',
            'vars' => array(
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'suffix' => $customer->getSuffix(),
                'prefix' => $customer->getPrefix(),
                'firstName' => $customer->getFirstName(),
                'middleName' => $customer->getMiddlename(),
                'lastName' => $customer->getLastName(),
                'address' => $customer->getAddresses(),
                //'attributes' => $customer->getAttributes(),
                'storeID' => $customer->getStoreId(),
                //'websiteId' => $customer->getWebsiteStoreId(),
                'groupId' => $customer->getGroupId(),
                'taxClassId' => $customer->getTaxClassId(),
                'createdAt' => date("Y-m-d H:i:s", $customer->getCreatedAtTimestamp()),
                'primaryBillingAddress' => $customer->getPrimaryBillingAddress(),
                'defaultBillingAddress' => $customer->getDefaultBillingAddress(),
                'defaultShippingAddress' => $customer->getDefaultShippingAddress(),
                'regionId' => $customer->getRegionId(),
                'zipCode' => $customer->getPostCode(),

            ),
            //Feel free to modify the lists below to suit your needs
            //You can read up documentation on http://getstarted.sailthru.com/api/user
            'lists' => array(Mage::helper('sailthruemail')->getMasterList() => 1)

        );

        return $data;
    }

    public function pushIncompletePurchaseOrderToSailthru(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

        $this->_getSailthruClient()->log($observer->getData(), "OBSERVER");
        $this->_getSailthruClient()->log($observer->getEvent()->getData(), "EVENT");

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $email   =  $customer->getEmail();

        if(!$email){
            //Do nothing if user is not logged in. Purchase API requires an email
            //http://getstarted.sailthru.com/api/purchase
            return;
        }

        try{//Schedule Abandoned Cart Email if that option has been enabled.
            if (Mage::helper('sailthruemail')->sendAbandonedCartEmails()){
                $data = array("email" => $email,
                              "incomplete" => 1,
                              "items" => $this->shoppingCart(),
                              "reminder_time" => "+".Mage::helper('sailthruemail')->getAbandonedCartReminderTime()." min",
                              "reminder_template" => Mage::helper('sailthruemail')->getAbandonedCartTemplate(),
                              "message_id" => isset($_COOKIE['sailthru_bid']) ? $_COOKIE['sailthru_bid'] : null,
                             );
                $response = $this->_getSailthruClient()->apiPost("purchase", $data);
                //Mage::log($response);

                //For future iterations, use switch statement to handle multiple error messages.
                if($response["error"] == 14) {
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
                                          "content_html" => Mage::helper('sailthruemail')->createAbandonedCartHtmlContent(),
                                          "subject" => Mage::helper('sailthruemail')->getAbandonedCartSubject(),
                                          "from_name" => Mage::helper('sailthruemail')->getAbandonedCartSenderName(),
                                          "from_email" => Mage::helper('sailthruemail')->getAbandonedCartSenderEmail(),
                                          "is_link_tracking" => 1,
                                          "is_google_analytics" => 1
                                         );
                    $create_template = $this->_getSailthruClient()->apiPost('template', $new_template);
                   //Mage::log($create_template);

                   //Send Purchase Data
                    $response = $this->_getSailthruClient()->apiPost("purchase", $data);
                    //Mage::log($response);
                }
            } else {
                $data = array("email" => $email, "incomplete" => 1, "items" => $this->shoppingCart());
                $response = $this->_getSailthruClient()->apiPost("purchase", $data);
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
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

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

        try{
            //Update Customer Information
            $response = $sailthru->apiPost('user', $this->getCustomerData($customer));
        }catch(Exception $e) {

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
            //$this->_debug($items_in_cart);

            return $items_in_cart;
    }

    /**
     * Push product to Sailthru using Content API
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

        $product = $observer->getEvent()->getProduct();
        $productData = $this->getProductData($product);

        try {
             $response = $this->_getSailthruClient()->apiDelete('content', $productData);
             $this->_debug($response);
        } catch(Exception $e) {
            //deal with Exception
            Mage::logException($e);
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
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

         $product = $observer->getEvent()->getProduct();
        $productData = $this->getProductData($product);

        try {
             $response = $this->_getSailthruClient()->apiPost('content', $productData);
             //$this->_debug($response);
        } catch(Exception $e) {
            //deal with Exception
            Mage::logException($e);
        }

        return $this;
    }

    public function getProductData($product)
    {
        $data = array('url' => $product->getProductUrl(),
                      'title' => htmlspecialchars($product->getName()),
                      //'date' => '',
                      'spider' => 1,
                      'tags' => htmlspecialchars($product->getMetaKeyword()),
                      'vars' => array('price' => $product->getPrice(),
                                      'sku' => $product->getSku(),
                                      'description' => htmlspecialchars($product->getDescription()),
                                      'storeId' => '',
                                      'typeId' => $product->getTypeId(),
                                      'status' => $product->getStatus(),
                                      'categoryId' => $product->getCategoryId(),
                                      'categoryIds' => $product->getCategoryIds(),
                                      'websiteIds' => $product->getWebsiteIds(),
                                      'storeIds'  => $product->getStoreIds(),
                                      //'attributes' => $product->getAttributes(),
                                      'groupPrice' => $product->getGroupPrice(),
                                      'formatedPrice' => $product->getFormatedPrice(),
                                      'calculatedFinalPrice' => $product->getCalculatedFinalPrice(),
                                      'minimalPrice' => $product->getMinimalPrice(),
                                      'specialPrice' => $product->getSpecialPrice(),
                                      'specialFromDate' => $product->getSpecialFromDate(),
                                      'specialToDate'  => $product->getSpecialToDate(),
                                      'relatedProductIds' => $product->getRelatedProductIds(),
                                      'upSellProductIds' => $product->getUpSellProductIds(),
                                      'getCrossSellProductIds' => $product->getCrossSellProductIds(),
                                      'isSuperGroup' => $product->isSuperGroup(),
                                      'isGrouped'   => $product->isGrouped(),
                                      'isConfigurable'  => $product->isConfigurable(),
                                      'isSuper' => $product->isSuper(),
                                      'isSalable' => $product->isSalable(),
                                      'isAvailable'  => $product->isAvailable(),
                                      'isVirtual'  => $product->isVirtual(),
                                      'isRecurring' => $product->isRecurring(),
                                      'isInStock'  => $product->isInStock(),
                                      'weight'  => $product->getSku(),
                          )
            );

        // Add product images
        if(self::validateProductImage($product->getImage())) {
            $data['vars']['imageUrl'] = $product->getImageUrl();
        }
        if(self::validateProductImage($product->getSmallImage())) {
            $data['vars']['smallImageUrl'] = $product->getSmallImageUrl($width = 88, $height = 77);
        }
        if(self::validateProductImage($product->getThumbnail())) {
            $data['vars']['thumbnailUrl'] = $product->getThumbnailUrl($width = 75, $height = 75);
        }

        return $data;
    }

    public function setSailthruCookie($observer) {
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

        //Mage::log($observer->getName());

        $customer = $observer->getCustomer();
        //$customer = Mage::getSingleton('customer/session')->getCustomer();
        Mage::log($customer);
        //Mage::log($customer->getEmail());

        try{
            $data = array('id' => $customer->getEmail(),
                          'key' => 'email',
                          'fields' => array('keys' => 1)
            );

            $response = $this->_getSailthruClient()->apiGet('user', $data);
            Mage::log($response);

            $sailthru_hid = $response['keys']['cookie'];
            $cookie = Mage::getSingleton('core/cookie')->set('sailthru_hid', $sailthru_hid);


        }catch(Exception $e){

        }
    }

    public function unsetSailthruCookie($observer) {
        if (!Mage::helper('sailthruemail')->isEnabled()) {
            return;
        }

        //Mage::log($observer->getName());

        $customer = $observer->getCustomer();
        //$customer = Mage::getSingleton('customer/session')->getCustomer();
        //Mage::log($customer);
        //Mage::log($customer->getEmail());

        try{
            $cookie = Mage::getSingleton('core/cookie')->delete('sailthru_hid');
            Mage::log($cookie);

        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    private static function validateProductImage($image) {
        if(empty($image)) {
            return false;
        }

        if('no_selection' == $image) {
            return false;
        }

        return true;
    }
}
