<?php
    include_once("Mage/Core/Model/Email/Template.php");
    class Sailthru_Email_Model_Email_Template extends Mage_Core_Model_Email_Template {
        public function send($email, $name = null, array $variables = array()) {
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
            //ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
            //ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));
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
            $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');
            $mail->setFrom($this->getSenderEmail(), $this->getSenderName());
            try {
                //sailthru//
                $template_name = "magento-default-email-template";
                $temails = "";
                $vars = null;
                $evars = array();
                $options = array("behalf_email" => Mage::getStoreConfig('sailthru_options/email/sailthru_sender_email'));
                for($i = 0; $i < count($emails); $i++) {
                    $evars[$emails[$i]] = array("content" => $text, "subj" => $this->getProcessedTemplateSubject($variables));
                    $temails .= $emails[$i].",";
                }
                $temails = substr($temails, 0, -1);
                $sailthru = Mage::getSingleton('Sailthru_Email_Model_SailthruConfig')->getHandle();
                $success = $sailthru->multisend($template_name, $temails, $vars, $evars, $options);
                if($success["error"] == 14) {
                    $tempvars = array("content_html" => "{content}", "subject" => "{subj}");
                    $tempsuccess = $sailthru->saveTemplate($template_name, $tempvars);
                    $success = $sailthru->multisend($template_name, $temails, $vars, $evars, $options);
                    if($success["error"]) {
                        Mage::throwException($this->__($success["errormsg"]));
                    }
                }
                //sailthru//
                $this->_mail = null;
            }
            catch (Exception $e) {
                $this->_mail = null;
                Mage::logException($e);
                return false;
            }

            return true;
        }
    }
?>
