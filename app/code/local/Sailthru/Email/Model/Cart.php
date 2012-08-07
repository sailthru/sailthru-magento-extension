<?php
    class Sailthru_Email_Model_Cart extends Mage_Checkout_Model_Cart {
        public function addProduct($productInfo, $requestInfo=null) {
            $product = $this->_getProduct($productInfo);
            $request = $this->_getProductRequest($requestInfo);

            $productId = $product->getId();

            if ($product->getStockItem()) {
                $minimumQty = $product->getStockItem()->getMinSaleQty();
                //If product was not found in cart and there is set minimal qty for it
                if ($minimumQty && $minimumQty > 0 && $request->getQty() < $minimumQty
                    && !$this->getQuote()->hasProductId($productId)
                ){
                    $request->setQty($minimumQty);
                }
            }

            if ($productId) {
                try {
                    $result = $this->getQuote()->addProduct($product, $request);
                } catch (Mage_Core_Exception $e) {
                    $this->getCheckoutSession()->setUseNotice(false);
                    $result = $e->getMessage();
                }
                /**
                 * String we can get if prepare process has error
                 */
                if (is_string($result)) {
                    $redirectUrl = ($product->hasOptionsValidationFail())
                        ? $product->getUrlModel()->getUrl(
                            $product,
                            array('_query' => array('startcustomization' => 1))
                        )
                        : $product->getProductUrl();
                    $this->getCheckoutSession()->setRedirectUrl($redirectUrl);
                    if ($this->getCheckoutSession()->getUseNotice() === null) {
                        $this->getCheckoutSession()->setUseNotice(true);
                    }
                    Mage::throwException($result);
                }
            } else {
                Mage::throwException(Mage::helper('checkout')->__('The product does not exist.'));
            }

            Mage::dispatchEvent('checkout_cart_product_add_after', array('quote_item' => $result, 'product' => $product));
            $this->getCheckoutSession()->setLastAddedProductId($productId);
            //sailthru//
            $scust = Mage::getSingleton('customer/session')->getCustomer();
            $email = $scust->getEmail();
            if($email != "") {
                $template_name = "magento-abandoned-cart-template";
                $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
                $protoitems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                $items = array();
                $i = 0;
                foreach($protoitems as $obi) {
                    $items[$i] = array("qty" => $obi->getQty(), "title" => $obi->getName(), "price" => $obi["product"]->getPrice()*100, "id" => $obi->getSku(), "url" => $obi["product"]->getProductUrl());
                    $i++;
                }
                $data = array("email" => $email, "incomplete" => 1, "items" => $items, "reminder_time" => "+".Mage::getStoreConfig('sailthru_options/shopping_cart/sailthru_reminder_time')."min", "reminder_template" => $template_name);
                //check if template is already created
                $tempcheck = $sailthru->getTemplate($template_name);
                if($tempcheck["error"] == 14) {
                    $content = '<p>The following was in your cart:</p>
                                <ul>
                                {sum = 0}
                                {foreach profile.purchase_incomplete.items as i}
                                <li>{i.qty} <a href="{i.url}">{i.title}</a> for ${number(i.price*i.qty, 2)}</li>
                                {sum = sum+(i.price*i.qty)}
                                {/foreach}
                                <hr />
                                <li>Total: ${number(sum, 2)}</li>
                                </ul>';//use css here in future versions to style the email.
                    $tempvars = array("content_html" => $content, "subject" => "Abandoned Cart", "from_email" => Mage::getStoreConfig('sailthru_options/email/sailthru_sender_email'));
                    $tempcheck = $sailthru->saveTemplate($template_name, $tempvars);
                    if(isset($tempcheck["error"])) {
                        unset($data["reminder_template"]);
                        unset($data["reminder_time"]);
                    }
                }
                $response = $sailthru->apiPost("purchase", $data);
                if(isset($response["error"])) {
                    Mage::throwException($this->__($response["errormsg"]));
                }
            }
            //sailthru//
            return $this;
        }
    }
?>
