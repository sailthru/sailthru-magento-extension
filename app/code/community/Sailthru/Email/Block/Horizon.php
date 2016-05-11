<?php
/**
 * Sailthru Extension for Magento 
 * 
 * @category Sailthru
 * @package Sailthru_Email
 * @author Kwadwo Juantuah <support@sailthru.com>
 */


class Sailthru_Email_Block_Horizon extends Mage_Page_Block_Html_Head
{
    
    protected function _construct() {
        $this->setTemplate('sailthru/horizon.phtml');
    }
    
    public function getHorizonJavascript() {
        
         //$domain = Mage::getStoreConfig('sailthru/horizon/horizon_domain');
        $domain = Mage::helper('sailthruemail')->getHorizonDomain();
        $concierge = Mage::helper('sailthruemail')->isConciergeEnabled() ? ', concierge: { from: "top", threshold: 10}' : '';
        //$concierge = Mage::getStoreConfig(Sailthru_Email_Helper_Data::XML_PATH_CONCIERGE_ENABLED) ? ', concierge: { from: "top", threshold: 10}' : 'not true';
        
    $horizon = <<<EOD
        <!--BEGIN SAILTHRU HORIZON & CONCIERGE -->
            <script type="text/javascript">
            //<![CDATA[
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
                            domain: "<?php echo Mage::helper('sailthruemail')->getHorizonDomain(); ?>"
                        });
                    };
                })();
            //]]>
            </script>
            <!-- END SAILTHRU HORIZON & CONCIERGE -->
EOD;
        
        return $horizon;
    }

    public function setHorizonTags() {
        $product = Mage::registry('current_product');
        
        $this->addItem('meta', 'sailthru.title', $product->getName());
        $this->addItem('meta', 'sailthru.tags', $product->getMetaKeyword());
        $this->addItem('meta', 'sailthru.description', $product->getDescription());
        $this->addItem('meta', 'sailthru.image.full', $product->getImageUrl());
        $this->addItem('meta', 'sailthru.image.thumb', $product->getThumbnailUrl(50, 50));
    }
    
    protected function _toHtml() {
        if (!Mage::helper('sailthruemail')->isHorizonEnabled()) {
            return '';
        }
        
        return parent::_toHtml();
    }
}