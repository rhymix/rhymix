<?php
    /**
     * @class  rssController
     * @author NHN (developers@xpressengine.com)
     * @brief rss module of the controller class
     *
     * Feed the document output
     *
     **/

    class rssController extends rss {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Check whether to use RSS rss url by adding
         **/
        function triggerRssUrlInsert() {
            $oModuleModel = &getModel('module');
            $total_config = $oModuleModel->getModuleConfig('rss');
            $current_module_srl = Context::get('module_srl');
            $site_module_info = Context::get('site_module_info');

            if(!$current_module_srl) {
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
            }

            if(!$current_module_srl) return new Object();
            // Imported rss settings of the selected module
            $oRssModel = &getModel('rss');
            $rss_config = $oRssModel->getRssModuleConfig($current_module_srl);

            if($rss_config->open_rss != 'N') {
                Context::set('rss_url', $oRssModel->getModuleFeedUrl(Context::get('vid'), Context::get('mid'), 'rss'));
                Context::set('atom_url', $oRssModel->getModuleFeedUrl(Context::get('vid'), Context::get('mid'), 'atom'));
            }

            if(Context::isInstalled() && $site_module_info->mid == Context::get('mid') && $total_config->use_total_feed != 'N') {
                if(Context::isAllowRewrite() && !Context::get('vid')) {
                    $request_uri = Context::getRequestUri();
                    Context::set('general_rss_url', $request_uri.'rss');
                    Context::set('general_atom_url', $request_uri.'atom');
                } else {
                    Context::set('general_rss_url', getUrl('','module','rss','act','rss'));
                    Context::set('general_atom_url', getUrl('','module','rss','act','atom'));
                }
            }

            return new Object();
        }
    }
?>
