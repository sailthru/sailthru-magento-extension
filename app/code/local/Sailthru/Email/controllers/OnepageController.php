<?php
include_once("Mage/Checkout/controllers/OnepageController.php");
class Sailthru_Email_OnepageController extends Mage_Checkout_OnepageController
{
    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        $result = array();

        try {
            if ($requiredAgreements = Mage::helper('checkout')
                    ->getRequiredAgreementIds()) {
                $postedAgreements = array_keys(
                    $this->getRequest()->getPost('agreement', array())
                );

                $message = 'Please agree to all the terms and conditions '
                . 'before placing the order.';

                if ($diff =
                        array_diff($requiredAgreements, $postedAgreements)) {
                    $result['success'] = false;
                    $result['error'] = true;
                    $result['error_messages'] = $this->__($message);
                    $this->getResponse()->setBody(
                        Mage::helper('core')->jsonEncode($result)
                    );

                    return;
                }
            }
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()
                    ->importData($data);
            }

            $this->getOnepage()->saveOrder();
            $storeId = Mage::app()->getStore()->getId();
            $paymentHelper = Mage::helper("payment");
            $zeroSubTotalPaymentAction = $paymentHelper
                ->getZeroSubTotalPaymentAutomaticInvoice($storeId);

            if ($paymentHelper->isZeroSubTotal($storeId)
                    && $this->_getOrder()->getGrandTotal() == 0
                    && $zeroSubTotalPaymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
                    && $paymentHelper->getZeroSubTotalOrderStatus($storeId) == 'pending') {
                $invoice = $this->_initInvoice();
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->save();
            }

            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error']   = false;
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $message = $e->getMessage();
            if( !empty($message) ) {
                $result['error_messages'] = $message;
            }
            $result['goto_section'] = 'payment';
            $result['update_section'] = array(
                'name' => 'payment-method',
                'html' => $this->_getPaymentMethodsHtml()
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail(
                $this->getOnepage()->getQuote(), $e->getMessage()
            );

            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            if ($gotoSection =
                    $this->getOnepage()->getCheckout()->getGotoSection()) {
                $result['goto_section'] = $gotoSection;
                $this->getOnepage()->getCheckout()->setGotoSection(null);
            }

            if ($updateSection =
                    $this->getOnepage()->getCheckout()->getUpdateSection()) {
                if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                    $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                    $result['update_section'] = array(
                        'name' => $updateSection,
                        'html' => $this->$updateSectionFunction()
                    );
                }
                $this->getOnepage()->getCheckout()->setUpdateSection(null);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail(
                $this->getOnepage()->getQuote(), $e->getMessage()
            );

            $message = 'There was an error processing your order. '
            . 'Please contact us or try again later.';

            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = $this->__($message);
        }

        $this->getOnepage()->getQuote()->save();

        // When there is a redirect to a third party, we don't want to save
        // the order yet, so we save the order in the return action.
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
