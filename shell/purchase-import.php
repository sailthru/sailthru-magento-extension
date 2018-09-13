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

        $memStart = memory_get_usage();

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection
//            ->addFieldToFilter('status', 'complete');
//            ->addAddressFields()
            ->setPageSize(200)
            ->load();

        echo "Processing {$collection->getSize()} orders.\n";
        $memCollection = memory_get_usage();

        $storeId = $this->getArg("store");
        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId)->load();
        }

        $total = 0;
        $implodeTotal = 0;
        $page = 1;
        $lastPage = $collection->getLastPageNumber();

        $startTime = microtime(true);
        $path = $this->_getRootPath() . self::$FILE_PATH;
        $writefile = fopen($path,"w");
        $helper = Mage::helper('sailthruemail/purchase');
        do {
            $collection->setCurPage($page++)->load();
            $exportData = $helper->generateExportData($collection);
            $total += count($exportData);
            $dataString = implode("\n", $exportData);
            fwrite($writefile, $dataString);
            $collection->clear();
            $implodeTotal += count(explode("\n", $dataString));
            if (i % 200 == 0) echo ".";
        } while ($page <= $lastPage);
        fclose($writefile);
        $time = microtime(true) - $startTime;


        $memData = memory_get_usage();

//        $json = json_encode($exportData);

        $memJson = memory_get_usage();
        echo "Finished.\n";
        echo print_r([
            "total" => $total,
            "implodeTotal" => $implodeTotal,
            "time" => $time,
            "pageSize" => $collection->getPageSize(),
            "start" => $this->convert($memStart),
            "memCollection" => $this->convert($memCollection),
            "memData" => $this->convert($memData),
            "memJson" => $this->convert($memJson),
            "dData" => $this->convert($memData - $memCollection),
            "dJson" => $this->convert($memJson - $memData)
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