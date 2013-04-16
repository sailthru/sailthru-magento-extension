<?php
/**
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 * @author    Kwadwo Juantuah <support@sailthru.com>
 */

class Sailthru_Email_Model_Newsletter_Template extends Mage_Newsletter_Model_Template 
{
    /**
     * Send mail to subscriber
     *
     * @param   Mage_Newsletter_Model_Subscriber|string   $subscriber   subscriber Model or E-mail
     * @param   array                                     $variables    template variables
     * @param   string|null                               $name         receiver name (if subscriber model not specified)
     * @param   Mage_Newsletter_Model_Queue|null          $queue        queue model, used for problems reporting.
     * @return boolean
     * @deprecated since 1.4.0.1
     **/
    public function send(
        $subscriber, array $variables = array(),
        $name=null, Mage_Newsletter_Model_Queue $queue=null)
    {
        //Check to see if module is enabled, otherwise return parent
        if (!Mage::helper('sailthruemail')->isTransactionalEmailEnabled()) {
            return parent::send($subscriber, $variables, $name, $queue);
        }
        
        if (!$this->isValidForSend()) {
            return false;
        }

        $logMessage =
            'Sailthru module is enabled, '
                . 'sending email in Sailthru_Email_Model_Newsletter_Template'
        Mage::log($logMessage);

        $email = '';
        if ($subscriber instanceof Mage_Newsletter_Model_Subscriber) {
            $email = $subscriber->getSubscriberEmail();
            if (is_null($name) && ($subscriber->hasCustomerFirstname()
                || $subscriber->hasCustomerLastname()) ) {
                $name = $subscriber->getCustomerFirstname()
                . ' '
                . $subscriber->getCustomerLastname();
            }
        }
        else {
            $email = (string) $subscriber;
        }

        if (Mage::getStoreConfigFlag(
            Mage_Core_Model_Email_Template::XML_PATH_SENDING_SET_RETURN_PATH)) {
            $this->getMail()->setReturnPath($this->getTemplateSenderEmail());
        }

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();
        $mail->addTo($email, $name);
        $text = $this->getProcessedTemplate($variables, true);

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        }
        else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject($this->getProcessedTemplateSubject($variables));
        $mail->setFrom(
            $this->getTemplateSenderEmail(), $this->getTemplateSenderName()
        );

        // Sailthru code should go in the try/catch block below
        try {
            $mail->send();
            $this->_mail = null;
            if (!is_null($queue)) {
                $subscriber->received($queue);
            }
        } catch (Exception $e) {
            if ($subscriber instanceof Mage_Newsletter_Model_Subscriber) {
                // If letter sent for subscriber, we create a problem report entry
                $problem = Mage::getModel('newsletter/problem');
                $problem->addSubscriberData($subscriber);
                if (!is_null($queue)) {
                    $problem->addQueueData($queue);
                }
                $problem->addErrorData($e);
                $problem->save();

                if (!is_null($queue)) {
                    $subscriber->received($queue);
                }
            } else {
                // Otherwise throw error to upper level
                throw $e;
            }
            return false;
        }

        return true;
    }
}


?>
