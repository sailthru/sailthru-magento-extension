<?php
    include_once("Mage/Customer/controllers/AddressController.php");
    class Sailthru_Email_AddressController extends Mage_Customer_AddressController {
        public function formPostAction() {
            if (!$this->_validateFormKey()) {
                return $this->_redirect('*/*/');
            }
            // Save data
            if ($this->getRequest()->isPost()) {
                $customer = $this->_getSession()->getCustomer();
                /* @var $address Mage_Customer_Model_Address */
                $address  = Mage::getModel('customer/address');
                $addressId = $this->getRequest()->getParam('id');
                if ($addressId) {
                    $existsAddress = $customer->getAddressById($addressId);
                    if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                        $address->setId($existsAddress->getId());
                    }
                }

                $errors = array();

                /* @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('customer_address_edit')
                    ->setEntity($address);
                $addressData    = $addressForm->extractData($this->getRequest());
                $addressErrors  = $addressForm->validateData($addressData);
                if ($addressErrors !== true) {
                    $errors = $addressErrors;
                }

                try {
                    $addressForm->compactData($addressData);
                    $address->setCustomerId($customer->getId())
                        ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                        ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));

                    $addressErrors = $address->validate();
                    if ($addressErrors !== true) {
                        $errors = array_merge($errors, $addressErrors);
                    }

                    if (count($errors) === 0) {
                        $address->save();
                        $this->_getSession()->addSuccess($this->__('The address has been saved.'));
                        $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                        //sailthru//
                        $email = $customer->getEmail();
                        $vars = array();
                        $addresses = $this->getRequest()->getParam('street', false);
                        $vars["address1"] = $addresses[0];
                        $vars["address2"] = $addresses[1];
                        $vars["phone"] = $this->getRequest()->getParam('telephone');
                        $vars["fax"] = $this->getRequest()->getParam('fax');
                        $vars["country"] = $this->getRequest()->getParam('country_id');
                        $vars["zip"] = $this->getRequest()->getParam('postcode');
                        $vars["city"] = $this->getRequest()->getParam('city');
                        $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
                        $sailthru->setEmail($email, $vars);
                        //sailthru//
                        return;
                    } else {
                        $this->_getSession()->setAddressFormData($this->getRequest()->getPost());
                        foreach ($errors as $errorMessage) {
                            $this->_getSession()->addError($errorMessage);
                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                        ->addException($e, $e->getMessage());
                } catch (Exception $e) {
                    $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                        ->addException($e, $this->__('Cannot save address.'));
                }
            }

            return $this->_redirectError(Mage::getUrl('*/*/edit', array('id' => $address->getId())));
        }
    }
?>
