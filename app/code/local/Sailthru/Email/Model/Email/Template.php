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

        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }
        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();
        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);

        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($returnPathEmail !== null) {
            $mailTransport = new Zend_Mail_Transport_Sendmail("-f".$returnPathEmail);
            Zend_Mail::setDefaultTransport($mailTransport);
        }

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        //Prevent Zend_Mail "Subject Set Twice" Error
        $mail_subject = '=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=';
        $mail->clearSubject();
        $mail->setSubject($mail_subject);

        //Prevent Zend_Mail "From Set Twice" Error
        $mail_from_email = $this->getSenderEmail();
        $mail_from_name = $this->getSenderName();
        $mail->clearFrom();
        $mail->setFrom($mail_from_email, $mail_from_name);

        //sailthru//
        try {
            if ($this->getData('template_code')) {
                $template_name = $this->getData('template_code');
            } else {
                $template_name = $this->getId();
            }
            
            $options = array(
                'behalf_email' => $this->getSenderEmail(),
            );

            $email = $emails;
            $vars = null;
            $evars = array();

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


    /**
     * Override transactional emails
     *
     * @param   int $templateId
     * @param   string|array $sender sneder informatio, can be declared as part of config path
     * @param   string $email recipient email
     * @param   string $name recipient name
     * @param   array $vars varianles which can be used in template
     * @param   int|null $storeId
     * @return  Mage_Core_Model_Email_Template
     * 
     * Create as send is being deprecated
    public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
        return $this;
    }
    */
}
