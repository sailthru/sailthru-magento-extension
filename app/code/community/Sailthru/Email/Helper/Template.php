<?php
/**
 * @package   Sailthru_Email
 */
class Sailthru_Email_Helper_Template extends Mage_Core_Helper_Abstract {

    public function getCustomerRegisterVars($vars)
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


    /**
     * Processes Shipping Event variables into Sailthru Template variables
     * @param $vars array
     * @return array
     */
    public function getSailthruShippingVars($vars)
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
            'orderStatus'       => $order->getStatus(),
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