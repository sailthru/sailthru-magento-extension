<?php
/**
 * Client User Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 *
 */
class Sailthru_Email_Model_Client_User extends Sailthru_Email_Model_Client
{

    /**
     * Send customer data through API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     * @throws Sailthru_Client_Exception
     */
    public function postNewCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'Customer Registration';
        $data = $this->_buildCustomerPayload($customer, "signup");
        $response = $this->apiPost('user', $data);
        $this->setCookie($response);
        $this->_setSid($customer, $response);
    }

    /**
     * Update customer in Sailthru upon change in Magento
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     * @throws Sailthru_Client_Exception
     */
    public function updateCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'Customer Update';
        $data = $this->_buildCustomerPayload($customer, "update");
        $response = $this->apiPost('user', $data);
        if (!$customer->getData('sailthru_id')) {
            $this->_setSid($customer, $response);
        }
    }
    /**
     * Update user data, and drop cookie.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     * @throws Sailthru_Client_Exception
     */
    public function loginCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'Customer Login';
        $data = $this->_buildCustomerPayload($customer, "login");
        $response = $this->apiPost('user', $data);
        $this->setCookie($response);
        if (!$customer->getData('sailthru_id')) {
            $this->_setSid($customer, $response);
        }
    }

    /**
     * Send subscriber data through API
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return void
     * @throws Sailthru_Client_Exception
     */
    public function sendSubscriberData(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $this->_eventType = 'Subscription Update';
        $data = $this->_buildSubscriberPayload($subscriber);
        if ($data) {
            $response = $this->apiPost('user', $data);
            $this->setCookie($response);
        }
    }

    /**
     * Build User API Payload for customers
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param null|string                  $action
     *
     * @return array
     */
    private function _buildCustomerPayload(Mage_Customer_Model_Customer $customer, $action = null) {
        $data = [
            'id' => $customer->getData('sailthru_id') ?: $customer->getEmail(),
            'key' => $customer->getData('sailthru_id') ? "sid" : 'email',
            'fields' => array('keys' => 1)
        ];
        if ($data['key'] == "sid") {
            $data['keysconfict'] = 'merge';
            $data["keys"] = [ "email" => $customer->getEmail()];
        }

        if ($action == "signup" or $action == "update") {
            $data['vars'] = $this->_getCustomerVars($customer);
            if ($action == "signup" and $masterList = $this->_getMasterList()) {
                $data["lists"] = [$masterList => 1];
            }
        }

        return $data;
    }

    /**
     * Build User API payload for subscribers
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     *
     * @return array|null
     */
    private function _buildSubscriberPayload(Mage_Newsletter_Model_Subscriber $subscriber) {
        $data = [
            'id'     => $subscriber->getEmail(),
            'key'    => "email",
            'fields' => ["keys" => 1],
            'vars'   => [
                'website' => Mage::app()->getStore()->getWebsite()->getName(),
                'store' => Mage::app()->getStore()->getName(),
            ]
        ];
        if ($newsletterList = $this->_getNewsletterList() and
            in_array($subscriber->getStatus(), [
                Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED,
                Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED]
            )) {
            $data["lists"][$newsletterList] = ($subscriber->getStatus() ==  Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED ? 1 : 0);
        } else {
            return null;
        }

        return $data;
    }

    /**
     * Create array of customer values for API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    private function _getCustomerVars(Mage_Customer_Model_Customer $customer)
    {
        try {

            $vars = array(
                'magento_id' => $customer->getId(),
                'name' => $customer->getName(),
                'suffix' => $customer->getSuffix() ? $customer->getSuffix() : '',
                'prefix' => $customer->getPrefix() ? $customer->getPrefix() : '',
                'firstName' => $customer->getFirstname(),
                'middleName' => $customer->getMiddlename() ? $customer->getMiddlename() : '',
                'lastName' => $customer->getLastname(),
                'website' => Mage::app()->getStore()->getWebsite()->getName(),
                'store' => Mage::app()->getStore()->getName(),
                'customerGroup' => Mage::getModel('customer/group')->load($customer->getGroupId())->getCustomerGroupCode(),
                'created_date' => date("Y-m-d", $customer->getCreatedAtTimestamp()),
                'created_time' => $customer->getCreatedAtTimestamp(),
            );

            if ($primaryBillingAddress = $customer->getPrimaryBillingAddress()){
                $vars["billingAddress"] = $this->_getAddressVars($primaryBillingAddress);
                $vars = $vars + $this->_getAddressVars($primaryBillingAddress, "billing_");
            }
            if ($primaryShippingAddress = $customer->getPrimaryShippingAddress()){
                $vars["shippingAddress"] = $this->_getAddressVars($primaryShippingAddress);
                $vars = $vars + $this->_getAddressVars($primaryShippingAddress, "shipping_");
            }

            return $vars;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get the name of the Master List, if enabled. Otherwise, return false.
     * @return bool|string
     */
    private function _getMasterList() {
        if (Mage::helper('sailthruemail')->isMasterListEnabled()
                and $masterList = Mage::helper('sailthruemail')->getMasterList()) {
                return $masterList;
        }
        return false;
    }

    /**
     * Get the name of the Newsletter List, if enabled. Otherwise return false.
     * @return bool|string
     */
    private function _getNewsletterList() {
        if (Mage::helper('sailthruemail')->isNewsletterListEnabled()
            and $newsletterList = Mage::helper('sailthruemail')->getNewsletterList()) {
            return $newsletterList;
        }
        return false;
    }

    /**
     * @param Mage_Customer_Model_Address $address
     * @param string $prefix
     *
     * @return string[]
     */
    public function _getAddressVars(Mage_Customer_Model_Address $address, $prefix=null){
        $vars = [
            "name"          => $address->getName(),
            "company"       => $address->getCompany(),
            "city"          => $address->getCity(),
            "state"         => $address->getRegion(),
            "state_code"    => $address->getRegionCode(),
            "country_code"  => $address->getCountry(),
            "postal_code"   => $address->getPostcode(),
            "telephone"     => $address->getTelephone(),
            "fax"           => $address->getFax(),
        ];
        if (!is_null($prefix)){
            $varsPrefixed = [];
            foreach ($vars as $key => $value) {
                $varsPrefixed["{$prefix}{$key}"] = $value;
            }
            return $varsPrefixed;
        }
        return $vars;
    }

    /**
     * Set Sailthru ID on customer
     * @param Mage_Customer_Model_Customer $customer
     * @param array                        $sailApiResponse
     */
    public function _setSid(Mage_Customer_Model_Customer $customer, $sailApiResponse)
    {
        if (in_array("keys", $sailApiResponse)) {
           $sid = $sailApiResponse["keys"]["sid"];
           $customer->setData("sailthru_id", $sid);
           $customer->save();
        }
    }

}
