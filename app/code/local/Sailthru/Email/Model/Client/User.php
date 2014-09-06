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
    public function getCustomerData(Mage_Customer_Model_Customer $customer)
    {
        try {
            if ($primaryBillingAddress = $customer->getPrimaryBillingAddress()) {
                $address = implode(', ',$primaryBillingAddress->getStreet());
                $state = $customer->getPrimaryBillingAddress()->getRegion();
                $zipcode = $customer->getPrimaryBillingAddress()->getPostcode();
            } else {
                $address = '';
                $state= '';
                $zipcode= '';
            }

            $data = array(
                'id' => $customer->getEmail(),
                'key' => 'email',
                'fields' => array('keys' => 1),
                'keysconfict' => 'merge',
                'vars' => array(
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                    'suffix' => $customer->getSuffix() ? $customer->getSuffix() : '',
                    'prefix' => $customer->getPrefix() ? $customer->getPrefix() : '',
                    'firstName' => $customer->getFirstname(),
                    'middleName' => $customer->getMiddlename() ? $customer->getMiddlename() : '',
                    'lastName' => $customer->getLastname(),
                    'address' => $address,
                    'storeID' => $customer->getStoreId(),
                    'groupId' => $customer->getGroupId(),
                    'taxClassId' => $customer->getTaxClassId(),
                    'createdAt' => date("Y-m-d H:i:s", $customer->getCreatedAtTimestamp()),
                    'primaryBillingAddress' => $this->getAddress($customer->getPrimaryBillingAddress()),
                    'defaultBillingAddress' => $this->getAddress($customer->getDefaultBillingAddress()),
                    'defaultShippingAddress' => $this->getAddress($customer->getDefaultShippingAddress()),
                    'state' => $state,
                    'zipCode' => $zipcode
                 ),
                //Feel free to modify the lists below to suit your needs
                //You can read up documentation on http://getstarted.sailthru.com/api/user
                'lists' => array(Mage::helper('sailthruemail')->getMasterList() => 1)
            );
            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Create array of address values for API
     *
     * @param Varien Object $address
     * @return array
     */
    public function getAddress($address)
    {
        if ($address) {
            return array(
                'firstname' => $address->getFirstname(),
                'middlename' => $address->getMiddlename() ? $address->getMiddlename() : '',
                'lastname' => $address->getLastname(),
                'company' => $address->getCompany() ? $address->getCompany() : '',
                'city' => $address->getCity(),
                'address' => implode(', ',$address->getStreet()),
                'country' => $address->getCountryId(),
                'state' => $address->getRegion(),
                'postcode' => $address->getPostcode(),
                'telephone' => $address->getTelephone(),
                'fax' => $address->getFax() ? $address->getFax() : ''
            );
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
            $data = $this->getCustomerData($customer);
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
            Mage::getSingleton('customer/session')->unsSailthruHid();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}