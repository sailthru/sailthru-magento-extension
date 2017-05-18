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

    private $_transactionalType;

    const FAILURE_MESSAGE = "There was an error delivering your email. Please contact customer service";

    const FAILURE_MESSAGE_INTERNAL = "Sailthru error with delivery:";

    /**
     * Send mail to recipient
     *
     * @param   array|string       $email        E-mail(s)
     * @param   array|string|null  $name         receiver name(s)
     * @param   array              $variables    template variables
     * @return  boolean
     * @throws Exception
     **/
    public function send($email, $name = null, array $variables = array())
    {
        $storeId = Mage::app()->getStore()->getStoreId();

        if(!Mage::helper('sailthruemail')->isEnabled($storeId) || !Mage::helper('sailthruemail')->isTransactionalEmailEnabled($storeId)){
            return parent::send($email, $name, $variables);
        }

       if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);

        $this->_transactionalType = Mage::helper('sailthruemail/template')->getTransactionalType($this->getId());
        if ($template_name = Mage::getStoreConfig($this->_transactionalType)) {
            $vars = Mage::helper('sailthruemail/template')->getTransactionalVars($this->_transactionalType, $variables);
        } else {
            $this->setUseAbsoluteLinks(true);
            if ($this->getData('template_code')) {
                $template_name = $this->getData('template_code');
            } else {
                $template_name = $this->getId();
            }
            $vars = [
                "content" => $this->getProcessedTemplate($variables),
                "subject" => $this->getProcessedTemplateSubject($variables)
            ];
        }

        $options = [];
        if (count($this->_bccEmails) > 0){
            $options['headers'] = [ 'Bcc' => $this->_bccEmails[0]];
        }

        $client =  Mage::getModel('sailthruemail/client');
        try {
            $client->multisend($template_name, $emails, $vars, null, $options);
            return true;
        } catch (Sailthru_Client_Exception $e) {
            // retry logic if 14 (a dynamic template that hasn't been created yet)
            if ($e->getCode() == 14) {
                try {
                    $templateVars = array("content_html" => "{content} {beacon}", "subject" => "{subj}");
                    $client->apiPost('template', ["template"=>$template_name, "vars" => $templateVars]);
                    $client->multisend($template_name, $emails, $vars, null, $options);
                    return true;
                } catch (Sailthru_Client_Exception $err_two) {
                    Mage::logException($e);
                    $e = $err_two;
                }
            }
            Mage::logException($e);
            if ($storeId != 0) {
                if (Mage::helper('sailthruemail')->isCustomerErrorEnabled($storeId)) {
                    $message = Mage::helper('sailthruemail')->getCustomerErrorMessage($storeId);
                    Mage::getSingleton('core/session')->addNotice(__($message));
                }
            } else {
                Mage::getSingleton('core/session')->addNotice(
                    self::FAILURE_MESSAGE_INTERNAL .
                    " <pre style='display: inline; margin-left: 5px;'>({$e->getCode()}) {$e->getMessage()}</pre>"
                );
                throw new Exception($e);
            }
            $this->_mail = null;
            return false;
        }
    }

}
