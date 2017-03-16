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
     * Create array of customer values for API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    private function getCustomerVars(Mage_Customer_Model_Customer $customer)
    {
        try {

            $vars = array(
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'suffix' => $customer->getSuffix() ? $customer->getSuffix() : '',
                'prefix' => $customer->getPrefix() ? $customer->getPrefix() : '',
                'firstName' => $customer->getFirstname(),
                'middleName' => $customer->getMiddlename() ? $customer->getMiddlename() : '',
                'fullName' => $customer->getFullName(),
                'lastName' => $customer->getLastname(),
                'website' => Mage::app()->getStore()->getWebsiteId(),
                'store' => Mage::app()->getStore()->getName(),
                'storeCode' => Mage::app()->getStore()->getCode(),
                'storeId' => Mage::app()->getStore()->getId(),
                'customerGroup' => $this->getCustomerGroup($customer),
                'taxClassId' => $customer->getTaxClassId(),
                'createdAt' => date("Y-m-d H:i:s", $customer->getCreatedAtTimestamp()),
             );

            if ($primaryBillingAddress = $customer->getPrimaryBillingAddress()){
                $vars = $vars + $this->getAddressInfo($primaryBillingAddress, "billing_");
            }
            if ($primaryShippingAddress = $customer->getPrimaryShippingAddress()){
                $vars = $vars + $this->getAddressInfo($primaryShippingAddress, "shipping_");
            }

            return $vars;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }


    /**
     * Send customer data through API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     */
    public function postNewCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'Customer Registration';
        try {
            $data = array(
                'id' => $customer->getEmail(),
                'key' => 'email',
                'fields' => array('keys' => 1),
                'keysconfict' => 'merge',
                'vars' => $this->getCustomerVars($customer),
            );

            $lists = [];
            if (Mage::helper('sailthruemail')->isMasterListEnabled()
                and $masterList = Mage::helper('sailthruemail')->getMasterList()) {
                $lists[$masterList] = 1;
            }
            if ($customer->getIsSubscribed() and Mage::helper('sailthruemail')->isMasterListEnabled()
                and $newsletterList = Mage::helper('sailthruemail')->getNewsletterList()) {
                $lists[$newsletterList] = 1;
            }
            if ($lists) $data["lists"] = $lists;

            $response = $this->apiPost('user', $data);
            $this->setCookie($response);

        } catch (Sailthru_Email_Model_Client_Exception $e) {
            Mage::logException($e);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Update user data, and drop cookie.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     */
    public function loginCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'Customer Login';

        try {
            $data = array(
                'id' => $customer->getEmail(),
                'key' => 'email',
                'fields' => array('keys' => 1),
                'keysconfict' => 'merge',
                'vars' => $this->getCustomerVars($customer),
            );
            $lists = [];
            if (Mage::helper('sailthruemail')->isMasterListEnabled()
                and $masterList = Mage::helper('sailthruemail')->getMasterList()) {
                $lists[$masterList] = 1;
            }
            if ($lists) $data["lists"] = $lists;
            $response = $this->apiPost('user', $data);
            $this->setCookie($response);
        } catch(Sailthru_Email_Model_Client_Exception $e) {
             Mage::logException($e);
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Send subscriber data through API
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return array
     *
     * @todo add unsubscribe functionality
     */
    public function sendSubscriberData(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $this->_eventType = 'subscriber';

        try {
            $data = array('id' => $subscriber->getSubscriberEmail(),
                'key' => 'email',
                'keys' => array('email' => $subscriber->getSubscriberEmail()),
                'keysconflict' => 'merge',
                 //this should be improved. Check to see if list is set.  Allow multiple lists.
                'lists' => array(Mage::helper('sailthruemail')->getNewsletterList() => 1),
                'vars' => $this->getCustomerVars(Mage::getModel('customer/customer')->load($subscriber->getCustomerId())),
                //Hacky way to prevent user from getting campaigns if they are not subscribed
                //An example is when a user has to confirm email before before getting newsletters.
                'optout_email' => ($subscriber->getSubscriberStatus() != 1) ? 'blast' : 'none',
            );
            $response = $this->apiPost('user', $data);
         } catch(Sailthru_Email_Model_Client_Exception $e) {
             Mage::logException($e);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }


    /**
     * Logout by deleting Sailthru session var
     */
    public function logout()
    {
        try {
            Mage::getModel('core/cookie')->delete('sailthru_hid');
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getAddressInfo($address, $prefix=null){
        $vars = [
            "city"          => $address->getCity(),
            "state"         => $address->getRegion(),
            "state_code"     => $address->getRegionCode(),
            "country_code"   => $address->getCountry(),
            "postal_code"        => $address->getPostcode(),
        ];
        if (!is_null($prefix)){
            $varsCopy = [];
            foreach ($vars as $key => $value) {
                $varsCopy["{$prefix}{$key}"] = $value;
            }
            return $varsCopy;
        }
        return $vars;
    }

    public function getCustomerGroup(Mage_Customer_Model_Customer $customer)
    {
        $groupId = $customer->getGroupId();
        return Mage::getModel('customer/group')->load($groupId)->getCustomerGroupCode();

    }


}
