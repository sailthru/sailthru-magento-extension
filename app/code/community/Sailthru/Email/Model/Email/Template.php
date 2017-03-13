<?php
/**
 * Email Template Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 *
 * This class overwrites Magento's default send functionality by routing all
 * emails through Sailthru using the Send API call.  Documentation can be found
 * online at http://getstarted.sailthru.com/api/send.
 *
 */
class Sailthru_Email_Model_Email_Template extends Mage_Core_Model_Email_Template {

    const ORDER_EMAIL                   = 'sailthru_transactional/templates/order';
    const SHIPPING_EMAIL                = 'sailthru_transactional/templates/shipping';
    const REGISTER_SUCCESS_EMAIL        = 'sailthru_transactional/templates/reg_success';
    const REGISTER_CONFIRM_EMAIL        = 'sailthru_transactional/templates/reg_confirm';
    const REGISTER_CONFIRMED_EMAIL      = 'sailthru_transactional/templates/reg_confirmed';
    const RESET_PASSWORD_EMAIL          = 'sailthru_transactional/templates/reset_password';
    const NEWSLETTER_CONFIRM_EMAIL      = 'sailthru_transactional/templates/newsletter_confirm';
    const NEWSLETTER_CONFIRMED_EMAIL    = 'sailthru_transactional/templates/newsletter_confirmed';
    const NEWSLETTER_UNSUBSCRIBE_EMAIL  = 'sailthru_transactional/templates/newsletter_unsubscribe';

    /**
     * Send mail to recipient
     *
     * @param   array|string       $email        E-mail(s)
     * @param   array|string|null  $name         receiver name(s)
     * @param   array              $variables    template variables
     * @return  boolean
     **/
    public function send($email, $name = null, array $variables = array())
    {
        /**
         * Return default parent method if Sailthru Extension
         * or Transactional Email has not been enabled
         */
        if(!Mage::helper('sailthruemail')->isEnabled() || !Mage::helper('sailthruemail')->isTransactionalEmailEnabled()){
            return parent::send($email, $name, $variables);
        }

       if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);
        Mage::log("CLASS:", null, "sailthru.log");
        Mage::log("These are the variables: ", null, "sailthru.log");
//        Mage::log(var_export($variables, true), null, "sailthru.log");
        $vars = $variables;
        //sailthru//
        try {
            if ($this->getData('template_code')) {
                $template_name = $this->getData('template_code');
            } else {
                $template_name = $this->getId();
            }
            
            $options = [
                'behalf_email' => $this->getSenderEmail(),
            ];
            if (count($this->_bccEmails) > 0){
                $options['headers'] = [ 'Bcc' => $this->_bccEmails[0]];
            }

            $num_emails = count($emails);
            for($i = 0; $i < $num_emails; $i++) {
                $evars[$emails[$i]] = array("content" => $text, "subj" => $this->getProcessedTemplateSubject($variables));
            }

            $client =  Mage::getModel('sailthruemail/client');
            $response = $client->multisend($template_name, $emails, $vars, $evars, $options);

            if(isset($response["error"]) && $response['error'] == 14) {
                //Create template if it does not already exist
                $tempvars = array("content_html" => "{content} {beacon}", "subject" => "{subj}");
                $tempsuccess = $client->saveTemplate($template_name, $tempvars);
                $response = $client->multisend($template_name, $emails, $vars, $evars, $options);
                if($response["error"]) {
                    Mage::throwException($this->__($response["errormsg"]));
                }
            }
        } catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);
            return false;
        }

        return true;
    }

    private function getTransactionalType()
    {
        $id = $this->getId();

        if ($id == Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE) or
            $id == Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE))
            return self::ORDER_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_TEMPLATE) or
            $id == Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_GUEST_TEMPLATE))
            return self::SHIPPING_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_REGISTER_EMAIL_TEMPLATE))
            return self::REGISTER_SUCCESS_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_CONFIRM_EMAIL_TEMPLATE))
            return self::REGISTER_CONFIRM_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_CONFIRMED_EMAIL_TEMPLATE))
            return self::REGISTER_CONFIRMED_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_REMIND_EMAIL_TEMPLATE))
            return self::RESET_PASSWORD_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRM_EMAIL_TEMPLATE))
            return self::NEWSLETTER_CONFIRM_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE))
            return self::NEWSLETTER_CONFIRMED_EMAIL;

        if ($id == Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE))
            return self::NEWSLETTER_UNSUBSCRIBE_EMAIL;

    }

    // TODO: Fill in the vars needed in Sailthru for each template type
    private function getTransactionalVars($transactionalType) {
        switch ($transactionalType):
            case self::SHIPPING_EMAIL:
                return [];
            case self::ORDER_EMAIL:
                return [];
            case self::REGISTER_SUCCESS_EMAIL:
                return [];
        endswitch;

    }
}
