<?php

class Sailthru_Email_Helper_Purchase extends Mage_Core_Helper_Abstract
{

    /**
     * Prepare data on items in cart or order.
     * @param $items Mage_Sales_Model_order_Item[]|Mage_Sales_Model_Quote_Item[]
     * @return array|bool
     */
    public function getTemplateOrderItems($items)
    {

        try {
            $data = array();
            foreach($items as $item) {
                $_item = array();
                $product = $item->getProduct();
                $options = $product->getTypeInstance(true)->getOrderOptions($product)['attributes_info'];
                if (!$options) {
                    $options = $item->getProductOptions()["attributes_info"];
                }

                $_item['options'] = $this->getVars($options, true);
                $_item['sku'] = $product->getSku();
                $_item['title'] = $product->getName();
                $_item['url'] = $product->getProductUrl();
                $_item['qty'] = intval($item->getQtyOrdered());
                $_item['url'] = $item->getProduct()->getProductUrl();
                $_item['price'] = $item->getProduct()->getFinalPrice();

                // NOTE: Thumbnail comes from cache, so if cache is flushed the THUMBNAIL may be inaccurate.
                $_item['image'] = Mage::helper('sailthruemail')->getProductImages($product);

                if ($tags = Mage::helper('sailthruemail')->getTags($item->getProduct())) {
                    $_item['tags'] = $tags;
                }

                $data[] = $_item;
            }

            return $data;
        } catch (Exception $e) {
            Mage::log("EXCEPTION: {$e->getMessage()}", null, "sailthru.log");
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @param string                 $action
     * @return array
     */
    public function getAdjustments(Mage_Sales_Model_Order $order, $action=null)
    {
        $adjustments = array(
                array('title' => 'Tax', 'price' => $order->getTaxAmount()),
                array('title' => 'Shipping', 'price' => $order->getShippingAmount())
        );

        if ($discount = $order->getDiscountAmount() != 0) {
            $adjustments[] = array('title' => 'Sale', 'price' => $order->getDiscountAmount());
        }

        if (strtolower($action) == "api") {
            foreach ($adjustments as &$adjustment) {
                $adjustment['price'] = Mage::helper('sailthruemail')->formatAmount($adjustment['price']);
            }
        }

        return $adjustments;
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
     * @param bool  $keepLabelValue - used for iteration in zephyr with option.key, option.value
     * @return array
     */
    public function getVars($options, $keepLabelValue=false)
    {
        if (!$options) {
            return null;
        } elseif (array_key_exists('attributes_info', $options)) {
            $options = $options['attributes_info'];
        }

        if ($keepLabelValue) {
            return $options;
        }

        $vars = array();
        foreach($options as $attribute) {
            $vars[$attribute['label']] = $attribute['value'];
        }

        return $vars;
    }

    /**
     * @param Mage_Sales_Model_Order_Item|Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    public function getItemVars($item) {
        return $this->getVars($this->getAttributes($item));
    }

    /**
     * Resolve attributes
     * @param Mage_Sales_Model_Order_Item|Mage_Sales_Model_Quote_Item $item
     *
     * @return null
     */
    private function getAttributes($item) {
        $ATTRIBUTES_KEY = 'attributes_info';
        $product = $item->getProduct();
        $options = $product->getTypeInstance(true)->getOrderOptions($product);
        if (array_key_exists($ATTRIBUTES_KEY, $options)) {
            return $options[$ATTRIBUTES_KEY];
        }
        if (array_key_exists($ATTRIBUTES_KEY, $item->getProductOptions() ?: [])) {
            return $item->getProductOptions()[$ATTRIBUTES_KEY];
        }
        return null;
    }

}

