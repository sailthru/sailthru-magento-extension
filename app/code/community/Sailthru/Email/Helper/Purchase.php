<?php

class Sailthru_Email_Helper_Purchase extends Mage_Core_Helper_Abstract {

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
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getAdjustments(Mage_Sales_Model_Order $order)
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
    public function getTenders(Mage_Sales_Model_Order $order)
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
     *
     * @param array $options
     * @return array
     */
    public function getVars($options)
    {
        $vars = array();

        if (array_key_exists('attributes_info', $options)) {
            foreach($options['attributes_info'] as $attribute) {
                $vars[] = array($attribute['label'] => $attribute['value']);
            }
        }

        return $vars;
    }
}