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
     * Get customer data object
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    protected function getCustomerData(Mage_Customer_Model_Customer $customer)
    {
        try {
            $data = array(
                'id' => $customer->getEmail(),
                'key' => 'email',
                'fields' => array('keys' => 1),
                'keysconfict' => 'merge',
                'vars' => array(
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                    'suffix' => $customer->getSuffix(),
                    'prefix' => $customer->getPrefix(),
                    'firstName' => $customer->getFirstName(),
                    'middleName' => $customer->getMiddlename(),
                    'lastName' => $customer->getLastName(),
                    'address' => $customer->getAddresses(),
                    //'attributes' => $customer->getAttributes(),
                    'storeID' => $customer->getStoreId(),
                    //'websiteId' => $customer->getWebsiteStoreId(),
                    'groupId' => $customer->getGroupId(),
                    'taxClassId' => $customer->getTaxClassId(),
                    'createdAt' => date("Y-m-d H:i:s", $customer->getCreatedAtTimestamp()),
                    'primaryBillingAddress' => $customer->getPrimaryBillingAddress(),
                    'defaultBillingAddress' => $customer->getDefaultBillingAddress(),
                    'defaultShippingAddress' => $customer->getDefaultShippingAddress(),
                    'regionId' => $customer->getRegionId(),
                    'zipCode' => $customer->getPostCode(),
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
     * Send customer data through API
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function sendCustomerData(Mage_Customer_Model_Customer $customer)
    {
        try {
            $response = $this->apiPost('user', $this->getCustomerData($customer));
            $sailthru_hid = $response['keys']['cookie'];
            $cookie = Mage::getSingleton('core/cookie')->set('sailthru_hid', $sailthru_hid);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    public function sendSubscriberData($subscriber)
    {
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
            $sailthru_hid = $response['keys']['cookie'];
            $cookie = Mage::getSingleton('core/cookie')->set('sailthru_hid', $sailthru_hid);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

}