<?php
ini_set('display_errors', 1);
$options = getopt("", ['magento_root::', 'store_id::']);


// Check that the Magento root path is correct.
if (!empty($options['magento_root'])) {

    $mage_file =$options['magento_root'].'/app/Mage.php';
    echo $mage_file;

    if (file_exists( $mage_file) ) {
        require_once('/var/www/htdocs/app/Mage.php');
    } else {
        echo PHP_EOL;
        echo "Could not find $mage_file, please check that the magento_root parameter is correct";
        echo PHP_EOL;
        exit();
    }

} else {
    echo "Provide the path to the magento_root with magento_root=/path/to/file";
    exit();
}

// Check that the Magento root path is correct.
if (empty($options['output'])) {
        echo PHP_EOL;
        echo "Provide a parameter --output=/path/to/output/file for the saved results ";
        echo PHP_EOL;
        exit();
}

// use default store if no param is provided.
$store = !empty($options['store_id']) ? $options['store_id'] : 1;


// TODO
/**
 * Figure out the filters and params that should be added to the script.
 * For example do we need to filter on product_types, or a date range.
 */

$fp = fopen($file_path,"w");

$startTime = microtime(true);
$queryTime = microtime(true);

Mage::app();
$orders = Mage::getModel('sales/order')->getCollection()
    ->addFieldToFilter('store_id', $store)
    ->addFieldToFilter('status', 'complete');


foreach ($orders as $order) {

    $order->getAllVisibleItems();
    $orderItems = $order->getItemsCollection()
    ->addAttributeToSelect('*')
    // ->addAttributeToFilter('product_type', array('eq'=>'simple'))
    ->load();

    $purchaseClient = Mage::getModel('sailthruemail/client_purchase');
    $data['email'] = $order->getCustomerEmail();
    $data['date'] = $order->getCreatedAtStoreDate()->getIso();
    $data['items'] = $purchaseClient->getItems($order->getAllVisibleItems());
    $data['adjustments'] = Mage::helper('sailthruemail/purchase')->getAdjustments($order, "api");
    $data['tenders'] = Mage::helper('sailthruemail/purchase')->getTenders($order);
    $data['purchase_keys'] = array("extid" => $order->getIncrementId());

    fwrite($fp, json_encode( $data) ) ;
    echo 'OrderId:'.$order->getIncrementId().' | '. $data['email'] . PHP_EOL;

}
fclose($fp);

$endTime = microtime(true);
$time = $endTime - $startTime;
$queryTotal = $queryTime - $startTime;

echo "-------------------------------------------------------------".PHP_EOL;
echo "Queried orders in $queryTotal seconds".PHP_EOL;
echo "Generated Order JSON in $time seconds".PHP_EOL;
echo "File saved to $file_path".PHP_EOL;
echo "--------------------------------------------------------------".PHP_EOL;

echo PHP_EOL;