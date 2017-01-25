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
    public function getCustomerVars(Mage_Customer_Model_Customer $customer)
    {
        try {

            $vars = array(
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'suffix' => $customer->getSuffix() ? $customer->getSuffix() : '',
                'prefix' => $customer->getPrefix() ? $customer->getPrefix() : '',
                'firstName' => $customer->getFirstname(),
                'middleName' => $customer->getMiddlename() ? $customer->getMiddlename() : '',
                'lastName' => $customer->getLastname(),
                'storeID' => $customer->getStoreId(),
                'groupId' => $customer->getGroupId(),
                'taxClassId' => $customer->getTaxClassId(),
                'createdAt' => date("Y-m-d H:i:s", $customer->getCreatedAtTimestamp()),
             );

            if ($primaryBillingAddress = $customer->getPrimaryBillingAddress()){
                $vars += getAddressInfo($primaryBillingAddress, "billing_");
            }
            if ($primaryShippingAddress = $customer->getPrimaryShippingAddress()){
                $vars += getAddressInfo($primaryShippingAddress, "shipping_");
            }

            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }


    /**
     * Send customer data through API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     *
     * @todo add optin/optout functionality
     */
    public function sendCustomerData(Mage_Customer_Model_Customer $customer)
    {
        $this->_eventType = 'customer';

        try {
            $user_vars = $this->getCustomerVars($customer);
            $data = array(
                'id' => $customer->getEmail(),
                'key' => 'email',
                'fields' => array('keys' => 1),
                'keysconfict' => 'merge',
                'vars' => $vars,
                'lists' => array(Mage::helper('sailthruemail')->getMasterList() => 1)
            );
            $response = $this->apiPost('user', $data);
            $this->setCookie($response);
        } catch(Sailthru_Email_Model_Client_Exception $e) {
             Mage::logException($e);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
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
            $response = $this->apiPost('user', $data);
            $this->setCookie($response);
         } catch(Sailthru_Email_Model_Client_Exception $e) {
             Mage::logException($e);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * Send login data through API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @throws Sailthru_Email_Model_Client_Exception
     * @return boolean
     */
    public function login(Mage_Customer_Model_Customer $customer)
    {
        try {
            $this->_eventType = 'login';
            $data = array(
                    'id' => $customer->getEmail(),
                    'key' => 'email',
                    'fields' => array(
                        'keys' => 1,
                        'engagement' => 1,
                        'activity' => 1,
                        'email' => $customer->getEmail(),
                        'extid' => $customer->getEntityId()
                    ),
                    'login' => array(
                        'site' => Mage::helper('core/url')->getHomeUrl(),
                        'ip' => Mage::helper('core/http')->getRemoteAddr(true),
                        'user_agent' => Mage::helper('core/http')->getHttpUserAgent(true)
                    )
            );

            if ($response = $this->apiPost('user',$data)) {
                return $this->setCookie($response);
            } else {
                throw new Sailthru_Email_Model_Client_Exception("Response: {$response} is not a valid JSON");
            }
        } catch(Sailthru_Email_Model_Client_Exception $e) {
            Mage::logException($e);
        } catch(Exception $e) {
            Mage::logException($e);
        }
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
            foreach ($address_vars as $key => $value) {
                $varsCopy["{$prefix}{$key}"] = $value;
            }
            $vars = $varsCopy;
        }
        return $vars;
    }



}
