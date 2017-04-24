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
     * @return Sailthru_Email_Model_Client_Content
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
     * @return Sailthru_Email_Model_Client_Content
     */
    public function saveProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_eventType = 'adminSaveProduct';
        try {
            $data = $this->getProductData($product);
            if ($data) {
                $response = $this->apiPost('content', $data);
            }
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * Create Product array from Mage_Catalog_Model_Product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array|false
     */
    public function getProductData(Mage_Catalog_Model_Product $product)
    {
        $productType = $product->getTypeId();
        $isMaster = ($productType == 'configurable' or $productType == 'bundle');
        $updateMaster = Mage::helper('sailthruemail')->updateMasterProducts();
        if ($isMaster and !$updateMaster) {
            return false;
        }
        $isSimple = ($productType == 'simple');
        $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        $isVariant = ($isSimple and (sizeof($parents) == 1));
        $updateVariants = Mage::helper('sailthruemail')->updateVariantProducts();
        if ($isVariant and !$updateVariants) {
            return false;
        }

        $url = $isVariant
            ? Mage::helper('sailthruemail')->getVariantUrl($product, $parents[0])
            : $product->getUrlInStore();

        try {
            $productTypeId = $product->getTypeId();
            $data = array(
                'url' => $url,
                'keys' => ['sku' => $product->getSku()],
                'title' => htmlspecialchars($product->getName()),
                'price' => $product->getPrice(),
                'description' => urlencode($product->getDescription()),
                'tags' => Mage::helper('sailthruemail')->getTags($product),
                'vars' => array(
                    'sku' => $product->getSku(),
                    'storeId' => $product->getStoreId(),
                    'typeId' => $productTypeId,
                    'status' => $product->getStatus(),
                    'categoryId' => $product->getCategoryId(),
                    'categoryIds' => $product->getCategoryIds(),
                    'category' => $product->getCategory(),
                    'websiteIds' => $product->getWebsiteIds(),
                    'storeIds'  => $product->getStoreIds(),
                    'attributes' => Mage::helper('sailthruemail')->getProductAttributeValues($product),
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
                    'weight'  => $product->getWeight()
                )
            );

            if ($isSimple) $data['inventory'] = $product->getSize();

            // PRICE-FIXING CODE
            $data['price'] = Mage::helper('sailthruemail')->getPrice($product);

            // NOTE: Thumbnail comes from cache, so if cache is flushed the THUMBNAIL may be inaccurate.
            $data['images'] = [
                "full"     => Mage::helper('catalog/product')->getImageUrl($product),
                "small"     => Mage::helper('catalog/product')->getSmallImageUrl($product),
                "thumbnail" => Mage::helper('catalog/image')->init($product, 'thumbnail')->__toString(),
            ];


            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }

    }

}
