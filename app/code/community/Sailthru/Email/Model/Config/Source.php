<?php

class Sailthru_Email_Model_Config_Source extends Sailthru_Email_Model_Client
{

    private static $error_responses = array(
        2 => "Please check your API credentials",
        3 => "Please check your API credentials",
        5 => "Please check your API credentials",
        4 => "Disallowed IP. Please reach out to Sailthru support"
    );

    protected function processOptionArrayError(Sailthru_Client_Exception $e)
    {
        $error_message = array_key_exists($e->getCode(), self::$error_responses)
            ? self::$error_responses[$e->getCode()]
            : "({$e->getCode()}) {$e->getMessage()}";

        return array( array( 'value' => null, 'label' => ("error: {$error_message}")));
    }
}