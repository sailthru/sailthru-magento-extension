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
        if(!Mage::helper('sailthruemail')->isEnabled() || !Mage::helper('sailthruemail')->isTransactionalEmailEnabled()){
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

        try {

            $client =  Mage::getModel('sailthruemail/client');
            $response = $client->multisend($template_name, $emails, $vars, $evars, $options);

            // Create template if it does not already exist
            if(isset($response["error"]) && $response['error'] == 14) {
                $templateVars = array("content_html" => "{content} {beacon}", "subject" => "{subj}");
                $client->apiPost('template', ["template"=>$template_name, "vars" => $templateVars]);
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

}
