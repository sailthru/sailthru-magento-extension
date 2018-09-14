<?php

require_once 'abstract.php';

class Sailthru_Email_Purchase_Export_CLI extends Mage_Shell_Abstract {

    private static $FILE_PATH = "var/sailthru_purchases.json";

    private static $PAGE_SIZE = 250;

    /**
     * Run script
     *
     */
    public function run()
    {
        ini_set('display_errors', 1);
        ini_set('memory_limit', '1G');

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection();
        $storeId = $this->getArg("store") || $this->getArg("s");
        $toDate = strtotime($this->getArg("to"));

        $collection
            ->addFieldToFilter('status', 'complete')
            ->setPageSize(self::$PAGE_SIZE);

        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        if ($toDate) {
            $timestamp = $collection->formatDate($toDate);
            $collection->addFieldToFilter('created_at', [ "to" => $timestamp ]);
        }

        $this->startEcho($collection, $storeId, $toDate);

        $startTime = microtime(true);
        $page = 1;
        $lastPage = $collection->getLastPageNumber();
        $writefile = $this->getFile();
        $helper = Mage::helper('sailthruemail/purchase');
        do {
            $collection->setCurPage($page++)->load();
            $exportData = $helper->generateExportData($collection);
            $dataString = implode(PHP_EOL, $exportData).PHP_EOL;
            fwrite($writefile, $dataString);
            $collection->clear();
            echo ".";

        } while ($page <= $lastPage);
        fclose($writefile);


        $time = microtime(true) - $startTime;
        echo "Finished.\n";
        print_r([
            "time" => "$time seconds",
            "page size" => $collection->getPageSize(),
            "mem max" => $this->convert(memory_get_peak_usage())
        ]);
        echo "Sailthru Order Import JSON saved to ".self::$FILE_PATH.PHP_EOL;
    }

    function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    private function startEcho(Mage_Core_Model_Resource_Db_Collection_Abstract $collection, $storeId, $toDate) {
        $output = "Exporting orders";
        if ($storeId) $output .= " from store $storeId";
        if ($toDate) $output .= " through date " . $collection->formatDate($toDate);
        echo $output."...".PHP_EOL;
        echo "Counted {$collection->load()->getSize()} orders...".PHP_EOL;
        echo "Processing...";
    }

    private function getFile() {
        $path = $this->_getRootPath() . self::$FILE_PATH;
        return fopen($path,"w");
    }

}


$shell = new Sailthru_Email_Purchase_Export_CLI();
$shell->run();