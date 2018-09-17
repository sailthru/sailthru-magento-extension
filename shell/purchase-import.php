<?php

require_once 'abstract.php';

class Sailthru_Email_Purchase_Export_CLI extends Mage_Shell_Abstract
{

    const FILE_PATH = "var/sailthru_purchases.json";
    const PAGE_SIZE = 250;
    const STORE_FLAG = "s"; // set store ID filter
    const FROM_FLAG = "f";  // set date filter from
    const TO_FLAG = "t";    // set date filter to

    /** @var Mage_Sales_Model_Resource_Order_Collection */
    private $collection;

    /** @var bool|resource */
    private $writefile;

    /** @var Sailthru_Email_Helper_Purchase */
    private $helper;

    /** @var int */
    private $storeId;

    /** @var string */
    private $fromDate;

    /** @var string */
    private $toDate;

    public function __construct()
    {
        parent::__construct();
        $this->writefile = $this->getFile();
        if ($this->writefile === false) {
            die("Couldn't create export file " . self::FILE_PATH);
        }

        $this->collection = Mage::getModel('sales/order')->getCollection();
        $this->helper = Mage::helper('sailthruemail/purchase');
        $this->storeId = $this->getArg(self::STORE_FLAG);
        $this->fromDate = $this->collection->formatDate(strtotime($this->getArg(self::FROM_FLAG)));
        $this->toDate = $this->collection->formatDate(strtotime($this->getArg(self::TO_FLAG)));
    }

    /**
     * Run script
     *
     */
    public function run()
    {
        ini_set('display_errors', 1);
        ini_set('memory_limit', '1G');
        $this->setFilters();
        $this->debug();

        echo cyan("Counted {$this->collection->load()->getSize()} orders..." . PHP_EOL);
        echo cyan("Processing...");

        $startTime = microtime(true);
        $page = 1;
        $lastPage = $this->collection->getLastPageNumber();
        do {
            $this->collection->setCurPage($page++)->load();
            $exportData = $this->helper->generateExportData($this->collection);
            $dataString = implode(PHP_EOL, $exportData) . PHP_EOL;
            fwrite($this->writefile, $dataString);
            $this->collection->clear();
            echo ".";
        } while ($page <= $lastPage);
        fclose($this->writefile);
        $time = microtime(true) - $startTime;
        $this->finishPrint($time);
    }

    private function setFilters()
    {
        $output = "Exporting orders";
        $this->collection
//            ->addFieldToFilter('status', 'complete')
            ->setPageSize(self::PAGE_SIZE);

        if ($this->storeId) {
            $this->collection->addFieldToFilter('store_id', ["eq" => intval($this->storeId)]);
            $output .= " from store $this->storeId";
        }

        if ($this->fromDate) {
            $this->collection->addFieldToFilter("created_at", ["from" => $this->fromDate]);
            $output .= " from " . $this->fromDate;
        }

        if ($this->toDate) {
            $this->collection->addFieldToFilter('created_at', ["to" => $this->toDate]);
            $output .= " through " . $this->toDate;
        }
        echo bold($output) . "..." . PHP_EOL;
    }

    private function debug()
    {
        if ($this->getArg("d")) {
            echo yellow("---DEBUG SQL---" . PHP_EOL . $this->collection->getSelectSql(true) . PHP_EOL . "---END DEBUG---" . PHP_EOL);
        }
    }

    private function getFile()
    {
        $path = $this->_getRootPath() . self::FILE_PATH;
        return fopen($path, "w");
    }

    private function finishPrint($time)
    {
        $mem = convertBytes(memory_get_peak_usage());
        $time = round($time, 2);
        echo "Finished.".PHP_EOL;
        $res = <<<RESULTS
------------------------
time    : $time seconds
mem max : $mem
------------------------


RESULTS;
        echo green($res) . "Sailthru Order Import JSON saved to".bold($this::FILE_PATH).PHP_EOL.PHP_EOL;
    }

    public function usageHelp()
    {
        return <<<USAGE
\e[33mUsage:\e[0m
    php purchase-export.php [OPTIONS]

\e[33mArguments 
    \e[32m-s <int>     \e[0mSet Store ID Filter
    \e[32m-f <date>    \e[0mSet From Date Filter
    \e[32m-t <date>    \e[0mSet To Date Filter


USAGE;
    }

}

function convertBytes($size) // http://php.net/manual/en/function.memory-get-usage.php#96280
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}


function green($str)
{
    return "\e[32m".$str."\e[0m";
}

function bold($str)
{
    return "\e[01m".$str."\e[0m";
}

function yellow($str)
{
    return "\e[33m".$str."\e[0m";
}

function cyan($str)
{
    return "\e[36m".$str."\e[0m";
}

$shell = new Sailthru_Email_Purchase_Export_CLI();
$shell->run();