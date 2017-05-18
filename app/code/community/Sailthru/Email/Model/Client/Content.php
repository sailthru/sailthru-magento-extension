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
     * @return bool
     * @throws Sailthru_Client_Exception
     */
    public function deleteProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_eventType = 'adminDeleteProduct';
        $data = $this->getProductData($product);
        if ($data) {
            $this->apiDelete('content', $data);
            return true;
        }
        return false;
    }

    /**
     * Push product save to Sailthru using Content API
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     * @throws Sailthru_Client_Exception
     */
    public function saveProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_eventType = 'adminSaveProduct';
        $data = $this->getProductData($product);
        if ($data) {
            $this->apiPost('content', $data);
            return true;
        }
        return false;
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
            : $product->getProductUrl(false);

        $productTypeId = $product->getTypeId();
        $data = array(
            'url' => $url,
            'title' => htmlspecialchars($product->getName()),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'tags' => Mage::helper('sailthruemail')->getTags($product),
            'vars' => array(
                'sku' => $product->getSku(),
                'storeId' => $product->getStoreId(),
                'type' => $productTypeId,
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

        if ($isSimple) $data['inventory'] = $product->getStockItem()->getQty();

        // PRICE-FIXING CODE
        $data['price'] = Mage::helper('sailthruemail')->getPrice($product);

        // NOTE: Thumbnail comes from cache, so if cache is flushed the THUMBNAIL may be inaccurate.
        $data['images'] = Mage::helper('sailthruemail')->getProductImages($product);

        return $data;
    }
}
