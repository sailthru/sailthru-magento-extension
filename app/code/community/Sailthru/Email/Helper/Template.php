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
     * Determine transactional type for Sailthru send.
     * @param string @templateId
     * @return string
     */
    public function getTransactionalType($templateId)
    {

        if ($templateId == Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE) or
            $templateId == Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE))
            return self::ORDER_EMAIL;

        if ($templateId == Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_TEMPLATE) or
            $templateId == Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_GUEST_TEMPLATE))
            return self::SHIPPING_EMAIL;

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

    public function getPurchaseTemplateVars($vars)
    {
        /** @var $order Mage_Sales_Model_Order
         *  @var $billingAddress Mage_Sales_Model_Order_Address
         */
        $order = $vars['order'];
        $billingAddress = $vars['billing'];
        $paymentHtmlBlock = $vars['payment_html'];

        $vars = [
            'items'       => Mage::helper('sailthruemail/purchase')->getItems($order->getAllVisibleItems()),
            'adjustments' => Mage::helper('sailthruemail/purchase')->getAdjustments($order),
            'tenders'     => Mage::helper('sailthruemail/purchase')->getTenders($order),
            'paymentHTML' => $paymentHtmlBlock,
            'total'       => $order->getGrandTotal(),
            'subtotal'    => $order->getSubtotal(),
            'status'      => $order->getStatusLabel(),
            'orderId'     => $order->getIncrementId(),
            'date'        => $order->getCreatedAtDate(),
        ] + Mage::helper('sailthruemail')->getAddressVars($billingAddress, 'billing');

        if ($shippingAddress = $order->getShippingAddress()) {
            $vars = $vars + Mage::helper('sailthruemail')->getAddressVars($shippingAddress, 'shipping');
        }

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

        $sailVars = [
            'shipmentDescription' => $order->getShippingDescription(),
            'is_guest'          => $order->getCustomerIsGuest(),
            'orderStatus'       => $order->getStatusLabel(),
            'orderState'        => $order->getState(),
            'coupon_code'       => $order->getCouponCode(),
            'items'             => $this->getShippingItems($shipment->getAllItems()),
            'createdAt'         => $shipment->getCreatedAt(),
            'comment'       => $vars["comment"],
            'payment_html'  => $vars["payment_html"],
            'billingAddress' => Mage::getModel('sailthruemail/client_user')->getAddressInfo($order->getBillingAddress()),
            'shippingAddress' => Mage::getModel('sailthruemail/client_user')->getAddressInfo($shipment->getShippingAddress()),
            'shipmentId'        => $shipment->getIncrementId(),
            'shipmentComment'   => $vars["comment"],
            'orderId'           => $order->getIncrementId(),
            'trackingDetails'    => $this->getTrackingDetails($shipment),
            'shipmentItems'      => $this->getShippingItems($shipment->getAllItems()),
        ];

        return $sailVars;
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
            Mage::log($itemData, null, "sailthru.log");
            $itemsData[] = $itemData;
        }
        return $itemsData;
    }
}