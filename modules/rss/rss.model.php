<?php
    /**
     * @class  rssModel
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 model class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssModel extends rss {

        /**
         * @brief 특정 모듈의 rss 설정을 return
         **/
        function getRssModuleConfig($module_srl) {
            // rss 모듈의 config를 가져옴
            $oModuleModel = &getModel('module');
            $rss_config = $oModuleModel->getModuleConfig('rss');

            $module_rss_config = $rss_config->module_config[$module_srl];

            if(!$module_rss_config->module_srl) {
                $module_rss_config->module_srl = $module_srl;
                $module_rss_config->open_rss = 'N';
            }
            return $module_rss_config;
        }
    }
?>
