<?php

require_once 'abstract.php';

class Sailthru_Email_Purchase_Export_CLI extends Mage_Shell_Abstract {

    private static $FILE_PATH = "var/sailthru_purchases.json";

    private static $PAGE_SIZE = 150;

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
        $collection
//            ->addFieldToFilter('status', 'complete');
            ->setPageSize(self::$PAGE_SIZE)
            ->load();

        echo "Processing {$collection->getSize()} orders.";

        $storeId = $this->getArg("store");
        if ($storeId) {
            echo "Using store filter $storeId";
            $collection->addFieldToFilter('store_id', $storeId)->load();
        }

        $page = 1;
        $lastPage = $collection->getLastPageNumber();

        $startTime = microtime(true);
        $path = $this->_getRootPath() . self::$FILE_PATH;
        $writefile = fopen($path,"w");
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
        echo print_r([
            "time" => "$time seconds",
            "page size" => $collection->getPageSize(),
            "mem max" => $this->convert(memory_get_peak_usage())
        ], true);
        echo "Sailthru Order Import JSON saved to ".self::$FILE_PATH.PHP_EOL;
    }

    function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    public function benchmark($pageSize)
    {

    }

}


$shell = new Sailthru_Email_Purchase_Export_CLI();
$shell->run();