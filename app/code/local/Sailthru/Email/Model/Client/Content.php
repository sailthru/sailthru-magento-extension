<?php
/**
 * Client Content Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 *
 */
class Sailthru_Email_Model_Client_Content extends Sailthru_Email_Model_Client
{

    /**
     * Push product delete to Sailthru using Content API
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Sailthru_Email_Model_Client_Product
     */
    public function deleteProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_eventType = 'adminDeleteProduct';

        try {
            $data = $this->getProductData($product);
            $response = $this->apiDelete('content', $data);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * Push product save to Sailthru using Content API
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Sailthru_Email_Model_Client_Product
     */
    public function saveProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_eventType = 'adminSaveProduct';

        try {
            $data = $this->getProductData($product);
            $response = $this->apiPost('content', $data);
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * Create Product array from Mage_Catalog_Model_Product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductData(Mage_Catalog_Model_Product $product)
    {
        try {
            $productTypeId = $product->getTypeId();
            $data = array('url' => $product->getProductUrl(),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $product->getPrice(),
                'description' => urlencode($product->getDescription()),
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'images' => array(),
                'vars' => array('sku' => $product->getSku(),
                    'storeId' => '',
                    'typeId' => $productTypeId,
                    'status' => $product->getStatus(),
                    'categoryId' => $product->getCategoryId(),
                    'categoryIds' => $product->getCategoryIds(),
                    'websiteIds' => $product->getWebsiteIds(),
                    'storeIds'  => $product->getStoreIds(),
                    //'attributes' => $product->getAttributes(),
                    'groupPrice' => $product->getGroupPrice(),
                    'formatedPrice' => $product->getFormatedPrice(),
                    'calculatedFinalPrice' => $product->getCalculatedFinalPrice(),
                    'minimalPrice' => $product->getMinimalPrice(),
                    'specialPrice' => $product->getSpecialPrice(),
                    'specialFromDate' => $product->getSpecialFromDate(),
                    'specialToDate'  => $product->getSpecialToDate(),
                    'relatedProductIds' => $product->getRelatedProductIds(),
                    'upSellProductIds' => $product->getUpSellProductIds(),
                    'getCrossSellProductIds' => $product->getCrossSellProductIds(),
                    'isSuperGroup' => $product->isSuperGroup(),
                    'isGrouped'   => $product->isGrouped(),
                    'isConfigurable'  => $product->isConfigurable(),
                    'isSuper' => $product->isSuper(),
                    'isSalable' => $product->isSalable(),
                    'isAvailable'  => $product->isAvailable(),
                    'isVirtual'  => $product->isVirtual(),
                    'isRecurring' => $product->isRecurring(),
                    'isInStock'  => $product->isInStock(),
                    'weight'  => $product->getSku()
                )
            );

            $current_price = null;
            $reg_price = null;
            if ($productTypeId == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                $reg_price = $product->getPriceModel()->getTotalPrices($product, 'min');
            } else {
                $reg_price = $product->getPrice();
            }
            $special_price = $product->getSpecialPrice();
            $special_from = $product->getSpecialFromDate();
            $special_to = $product->getSpecialToDate();
            if (!is_null($special_price) AND
                (is_null($special_from) or (strtotime($special_from) < strtotime("Today"))) AND
                (is_null($special_to) or (strtotime($special_to) > strtotime("Today")))) {
                $current_price = $special_price;
            } else {
                $current_price = $reg_price;
            } 
            $data["special_price"] = $special_price;
            $data["special_from"] = $special_from;
            $data["special_to"] = $special_to;
            $data["reg_price"] = $reg_price;
            $data['price'] = Mage::helper('sailthruemail')->formatAmount($current_price);

            // Add product images
            if(self::validateProductImage($product->getImage())) {
                $data['images']['full'] = array ("url" => $product->getImageUrl());
            }

            if(self::validateProductImage($product->getSmallImage())) {
                $data['images']['smallImage'] = array("url" => $product->getSmallImageUrl($width = 88, $height = 77));
            }

            if(self::validateProductImage($product->getThumbnail())) {
                $data['images']['thumb'] = array("url" => $product->getThumbnailUrl($width = 75, $height = 75));
            }


            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }

    }

    private static function validateProductImage($image) {
        if(empty($image)) {
            return false;
        }

        if('no_selection' == $image) {
            return false;
        }

        return true;
    }

}
