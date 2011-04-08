<?php
    /**
     * @class  rssModel
     * @author NHN (developers@xpressengine.com)
     * @brief The model class of the rss module
     *
     * Feed the document output
     *
     **/

    class rssModel extends rss {
        /**
         * @brief Create the Feed url.
         **/
        function getModuleFeedUrl($vid = null, $mid, $format) {
            if(Context::isAllowRewrite()) {
                $request_uri = Context::getRequestUri();
                // If the virtual site variable exists and it is different from mid (vid and mid should not be the same)
                if($vid && $vid != $mid) {
                    return $request_uri.$vid.'/'.$mid.'/'.$format;
                }
                else {
                    return $request_uri.$mid.'/'.$format;
                }
            }
            else {
                return getUrl('','mid',$mid,'act',$format);
            }
        }


        /**
         * @brief Return the RSS configurations of the specific modules
         **/
        function getRssModuleConfig($module_srl) {
            // Get the configurations of the rss module
            $oModuleModel = &getModel('module');
            $module_rss_config = $oModuleModel->getModulePartConfig('rss', $module_srl);
            if(!$module_rss_config) $module_rss_config->open_rss = 'N';
            $module_rss_config->module_srl = $module_srl;
            return $module_rss_config;
        }
    }
?>
