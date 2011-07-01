<?php
    class Sailthru_Email_Block_Page_Html_Head extends Mage_Page_Block_Html_Head {
        protected function _separateOtherHtmlHeadElements(&$lines, $itemIf, $itemType, $itemParams, $itemName, $itemThe) {
            $params = $itemParams ? ' ' . $itemParams : '';
            $href   = $itemName;
            switch ($itemType) {
                case 'rss':
                    $lines[$itemIf]['other'][] = sprintf('<link href="%s"%s rel="alternate" type="application/rss+xml" />',
                        $href, $params
                    );
                    break;
                case 'link_rel':
                    $lines[$itemIf]['other'][] = sprintf('<link%s href="%s" />', $params, $href);
                    break;
                    //sailthru//
                case 'js_inline':
                    $lines[$itemIf]['other'][] = sprintf('<script type="text/javascript">%s</script>', $params);
                    break;
                case 'meta':
                    $lines[$itemIf]['other'][] = sprintf('<meta name="%s" content="%s" />', $itemName, htmlspecialchars($itemParams));
                    break;
                    //sailthru
            }
        }
    }
