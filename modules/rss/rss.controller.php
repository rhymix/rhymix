<?php
    /**
     * @class  rssController
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 controller class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssController extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief RSS 사용 유무를 체크하여 rss url 추가
         **/
        function triggerRssUrlInsert() {
            $current_module_srl = Context::get('module_srl');

            if(!$current_module_srl) {
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
            }

            if(!$current_module_srl) return new Object();

            // 선택된 모듈의 rss설정을 가져옴
            $oRssModel = &getModel('rss');
            $rss_config = $oRssModel->getRssModuleConfig($current_module_srl);

            if($rss_config->open_rss != 'N') Context::set('rss_url', getUrl('','mid',Context::get('mid'),'act','rss'));
            if($rss_config->open_rss != 'N') Context::set('atom_url', getUrl('','mid',Context::get('mid'),'act','atom'));

            return new Object();
        }
    }
?>
