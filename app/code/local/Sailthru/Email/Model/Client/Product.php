<?php
/**
 * Client Purchase Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 *
 */
class Sailthru_Email_Model_Client_Product extends Sailthru_Email_Model_Client
{

    /**
     * Push product delete to Sailthru using Content API
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Sailthru_Email_Model_Client_Product
     */
    public function deleteProduct(Mage_Catalog_Model_Product $product)
    {
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
        try {
            $data = $this->getProductData($product);
            $response = $this->apiPost('content', $productData);
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
            $data = array('url' => $product->getProductUrl(),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'vars' => array('price' => $product->getPrice(),
                    'sku' => $product->getSku(),
                    'description' => htmlspecialchars($product->getDescription()),
                    'storeId' => '',
                    'typeId' => $product->getTypeId(),
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
                    'weight'  => $product->getSku(),
                )
            );

            // Add product images
            if($product->getImage()) {
                $data['vars']['imageUrl'] = $product->getImageUrl();
            }

            if($product->getSmallImage()) {
                $data['vars']['smallImageUrl'] = $product->getSmallImageUrl($width=88,$height =77);
            }

            if($product->getThumbnail()) {
                $data['vars']['thumbnailUrl'] = $product->getThumbnailUrl($width=75,$height=75);
            }

            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }

    }

}