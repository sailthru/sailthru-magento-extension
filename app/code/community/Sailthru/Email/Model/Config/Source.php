<?php

class Sailthru_Email_Model_Config_Source extends Sailthru_Email_Model_Client {

    private static $error_responses = [
        3 => "Please check your API credentials",
        5 => "Please check your API credentials",
        4 => "Disallowed IP. Please reach out to Sailthru support"
    ];

    protected function processOptionArrayError(Sailthru_Client_Exception $e)
    {
        $error_message = array_key_exists($e->getCode(), self::$error_responses)
            ? self::$error_responses[$e->getCode()]
            : "({$e->getCode()}) {$e->getMessage()}";

        /** @var Mage_Core_Model_Message_Collection $messages */
        /** @var Mage_Core_Model_Message_Error[] $errors */
//        $messages = Mage::getSingleton('core/session')->getMessages();
//        $errors = $messages->getErrors();
//        $sailError = false;
//        foreach ($errors as $error) {
//            if (strpos($error->getText(), "Sailthru")) $sailError = true;
//        }
//        if (!$sailError) {
//            $message = ($e->getCode() == 3) ? "Please enter valid API credentials." : $e->getMessage();
//            Mage::getSingleton('core/session')->addError("There was an error connecting to the Sailthru API: <pre>({$e->getCode()}) {$message}</pre>");
//        }
        return [ [ 'value' => 0, 'label' => ("--error-- {$error_message}")]];
    }
}