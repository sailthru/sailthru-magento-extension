<?php
/**
 * Product View block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @module     Catalog
 */
class Sailthru_Email_Block_Catalog_Product_View extends Mage_Catalog_Block_Product_View
{
    /**
     * Add meta information from product to head block
     *
     * @return Mage_Catalog_Block_Product_View
     */
    protected function _prepareLayout()
    {
        $this->getLayout()->createBlock('catalog/breadcrumbs');
        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $product = $this->getProduct();
            $title = $product->getMetaTitle();
            if ($title) {
                $headBlock->setTitle($title);
            }
            $keyword = $product->getMetaKeyword();
            $currentCategory = Mage::registry('current_category');
            if ($keyword) {
                $headBlock->setKeywords($keyword);
            } elseif($currentCategory) {
                $headBlock->setKeywords($product->getName());
            }
            $description = $product->getMetaDescription();
            if ($description) {
                $headBlock->setDescription( ($description) );
            } else {
                $headBlock->setDescription(Mage::helper('core/string')->substr($product->getDescription(), 0, 255));
            }
            if ($this->helper('catalog/product')->canUseCanonicalTag()) {
                $params = array('_ignore_category'=>true);
                $headBlock->addLinkRel('canonical', $product->getUrlModel()->getUrl($product, $params));
            }           
        }
        //sailthru//
        $domain = Mage::getStoreConfig('sailthru_options/horizon/sailthru_horizon_domain');
        if($domain) {
            $con = Mage::getStoreConfig('sailthru_options/horizon/sailthru_concierge_enabled');
            $consetup = $con ? ', concierge: { from: "top", threshold: 10}' : '';
            $sailthrujs = "
                (function() {
                    function loadHorizon() {
                        var s = document.createElement('script');
                        s.type = 'text/javascript';
                        s.async = true;
                        s.src = location.protocol + '//ak.sail-horizon.com/horizon/v1.js';
                        var x = document.getElementsByTagName('script')[0];
                        x.parentNode.insertBefore(s, x);
                    }
                    loadHorizon();
                    var oldOnLoad = window.onload;
                    window.onload = function() {
                        if (typeof oldOnLoad === 'function') {
                            oldOnLoad();
                        }
                        Sailthru.setup({
                                domain: '$domain'
                                $consetup
                        });
                    };
                })();";
            $db = Mage::getSingleton('core/resource')->getConnection('core_read');
            $url = $this->helper('catalog/image')->init($product, 'thumbnail')->resize(50, 50);
            $headBlock->addJs('jquery/jquery.min.js');
            $headBlock->addItem('js_inline', '', $sailthrujs);
            $headBlock->addItem('meta', 'sailthru.title', $product->getName());
            $headBlock->addItem('meta', 'sailthru.tags', $product->getMetaKeyword());
            $headBlock->addItem('meta', 'sailthru.image.thumb', $url);
            $headBlock->addItem('meta', 'sailthru.description', $product->getDescription());
            
        }
        //sailthru//
        return parent::_prepareLayout();
    }

}