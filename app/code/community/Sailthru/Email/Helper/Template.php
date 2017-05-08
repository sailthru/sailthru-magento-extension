<?php
/**
 * @package   Sailthru_Email
 */
class Sailthru_Email_Helper_Template extends Mage_Core_Helper_Abstract {

    const ORDER_EMAIL                   = 'sailthru_transactional/email/purchase_template';
    const SHIPPING_EMAIL                = 'sailthru_transactional/email/shipping';
    const REGISTER_SUCCESS_EMAIL        = 'sailthru_transactional/email/customer_register';
    const REGISTER_CONFIRM_EMAIL        = 'sailthru_transactional/email/customer_confirm';
    const REGISTER_CONFIRMED_EMAIL      = 'sailthru_transactional/email/customer_confirmed';
    const RESET_PASSWORD_EMAIL          = 'sailthru_transactional/email/reset_password';
    const NEWSLETTER_CONFIRM_EMAIL      = 'sailthru_transactional/email/newsletter_confirm';
    const NEWSLETTER_SUBSCRIBED_EMAIL   = 'sailthru_transactional/email/newsletter_subscribed';
    const NEWSLETTER_UNSUBSCRIBE_EMAIL  = 'sailthru_transactional/email/newsletter_unsubscribe';


    /**
     * Determine transactional type for Sailthru send. Not using switch for clearer grouping.
     * @param string @templateId
     * @return string
     */
    public function getTransactionalType($templateId)
    {

        // order templates
        if (in_array($templateId, [
            Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE),
            Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE)
            ])) {
            return self::ORDER_EMAIL;
        }

        // shipping emails
        if (in_array($templateId, [
            Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_TEMPLATE),
            Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_GUEST_TEMPLATE)
            ])) {
            return self::SHIPPING_EMAIL;
        }

        if ($templateId == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_REGISTER_EMAIL_TEMPLATE))
            return self::REGISTER_SUCCESS_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_CONFIRM_EMAIL_TEMPLATE))
            return self::REGISTER_CONFIRM_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_CONFIRMED_EMAIL_TEMPLATE))
            return self::REGISTER_CONFIRMED_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_REMIND_EMAIL_TEMPLATE))
            return self::RESET_PASSWORD_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRM_EMAIL_TEMPLATE))
            return self::NEWSLETTER_CONFIRM_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE))
            return self::NEWSLETTER_SUBSCRIBED_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE))
            return self::NEWSLETTER_UNSUBSCRIBE_EMAIL;
    }

    public function getTransactionalVars($transactionalType, $vars)
    {
        switch ($transactionalType):
            case self::SHIPPING_EMAIL:
                return $this->getShippingTemplateVars($vars);
            case self::REGISTER_SUCCESS_EMAIL:
                return $this->getCustomerRegisterTemplateVars($vars);
            case self::ORDER_EMAIL:
                return $this->getOrderTemplateVars($vars);
        endswitch;

    }

    public function getCustomerRegisterTemplateVars($vars)
    {
        /**
         * @var $customer Mage_Customer_Model_Customer
         * @var $backUrl string
         */
        $customer = $vars["customer"];
        $backUrl = $vars["back_url"];

        return [
            "name" => $customer->getName(),
            "email" => $customer->getEmail(),
            "back_url" => $backUrl
        ];
    }

    public function getOrderTemplateVars($vars)
    {
        /** @var $order Mage_Sales_Model_Order
         * @var $paymentHtmlBlock string
         */
        $paymentHtmlBlock = $vars['payment_html'];
        $order = $vars['order'];
        $vars = [
            "order"       => $this->_extractOrderVars($order),
            'paymentHtml' => $paymentHtmlBlock,
        ];

        return $vars;
    }

    /**
     * Processes Shipping Event variables into Sailthru Template variables
     * @param $vars array
     * @return array
     */
    public function getShippingTemplateVars($vars)
    {

        /**
         * @var Mage_Sales_Model_Order $order
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $order = $vars["order"];
        $shipment = $vars["shipment"];

        $shipment = [
            'id'                => $shipment->getIncrementId(),
            'items'             => $this->getShippingItems($shipment->getAllItems()),
            'created_date'      => $shipment->getCreatedAt(),
            'trackingDetails'   => $this->getTrackingDetails($shipment),
            'shipmentItems'     => $this->getShippingItems($shipment->getAllItems()),
            'comment'           => $vars["comment"],
            'payment_html'      => $vars["payment_html"],
            'address'           => Mage::getModel('sailthruemail/client_user')->getAddressInfo($shipment->getShippingAddress()),
        ];

        $order = $this->_extractOrderVars($order);

        return [
            "shipment" => $shipment,
            "order"    => $order,
        ];

    }

    /**
     * Get all vars we can extract always from an order. Does not return shipping address in case of multiple results for a shipment email
     * @param Mage_Sales_Model_Order $order
     *
     * @return array $data
     */
    private function _extractOrderVars(Mage_Sales_Model_Order $order)
    {
        Mage::log("creating order vars", null, "sailthru.log");
        $data = [
            'id'                  => $order->getIncrementId(),
            'isGuest'             => $order->getCustomerIsGuest(),
            'status'              => $order->getStatusLabel(),
            'state'               => $order->getState(),
            'created_date'        => $order->getCreatedAt(),
            'total'               => $order->getGrandTotal(),
            'subtotal'            => $order->getSubtotal(),
            'couponCode'          => $order->getCouponCode(),
            'discount'            => $order->getDiscountAmount(),
            'shippingDescription' => $order->getShippingDescription(),
            'items'               => Mage::helper('sailthruemail/purchase')->getTemplateOrderItems($order->getAllVisibleItems()),
            'adjustments'         => Mage::helper('sailthruemail/purchase')->getAdjustments($order),
            'tenders'             => Mage::helper('sailthruemail/purchase')->getTenders($order),
        ];

        if ($billingAddress = $order->getBillingAddress()) {
            $data['billingAddress'] = Mage::helper('sailthruemail')->getAddressVars($billingAddress, null, true);
        }

        if ($shippingAddress = $order->getShippingAddress()) {
            $data['shippingAddress'] = Mage::helper('sailthruemail')->getAddressVars($shippingAddress, null, true);
        }

        return $data;
    }

    private function getTrackingDetails(Mage_Sales_Model_Order_Shipment $shipment)
    {
        /**
         * @var Mage_Sales_Model_Order_Shipment_Track[] $tracks
         */
        $trackingDetails = [];
        $tracks = $shipment->getAllTracks();
        foreach ($tracks as $track) {
            $trackingDetails[] = ['by'=>$track->getTitle(), 'number'=>$track->getNumber()];
        }
        return $trackingDetails;
    }

    /**
     * @param $items Mage_Sales_Model_Order_Shipment_Item[]
     *
     * @return array
     */
    private function getShippingItems($items)
    {
        $itemsData = [];
        foreach($items as $item) {
            $item = $item->getOrderItem();
            $itemData = [
                "title" => $item->getName(),
                "options" => $item->getProductOptions()["attributes_info"],
            ];
            $itemsData[] = $itemData;
        }
        return $itemsData;
    }
}