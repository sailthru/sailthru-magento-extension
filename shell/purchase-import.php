<?php

require_once 'abstract.php';

class Sailthru_Email_Purchase_Export_CLI extends Mage_Shell_Abstract {

    private static $FILE_PATH = "var/sailthru_purchases.json";

    /**
     * Run script
     *
     */
    public function run()
    {
        ini_set('display_errors', 1);
        ini_set('memory_limit', '1G');

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', 'complete');

        echo "Processing {$collection->getSize()} orders...\n";

        $storeId = $this->getArg("store");
        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $exportData = Mage::helper('sailthruemail/purchase')->generateExportData($collection);
        $json = json_encode($exportData);
        echo "Finished processing.\n";

        $path = $this->_getRootPath() . self::$FILE_PATH;
        $fp = fopen($path,"w");
        fwrite($fp, $json);
        fclose($fp);
        echo "Sailthru Order Import JSON saved to ".self::$FILE_PATH.PHP_EOL;
    }
}

$shell = new Sailthru_Email_Purchase_Export_CLI();
$shell->run();