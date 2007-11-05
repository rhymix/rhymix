<?php
    /**
     * @class  rssAdminController
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 admin controller class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssAdminController extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief RSS 모듈별 설정
         **/
        function procRssAdminInsertModuleConfig() {
            // 필요한 변수를 받아옴
            $module_srl = Context::get('target_module_srl');
            $open_rss = Context::get('open_rss');
            if(!$module_srl || !$open_rss) return new Object(-1, 'msg_invalid_request');

            if(!in_array($open_rss, array('Y','H','N'))) $open_rss = 'N';

            // 설정 저장
            $output = $this->setRssModuleConfig($module_srl, $open_rss);

            $this->setMessage('success_registed');
        }

        /**
         * @brief RSS 모듈별 설정 함수
         **/
        function setRssModuleConfig($module_srl, $open_rss) {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            $rss_config = $oModuleModel->getModuleConfig('rss');
            $rss_config->module_config[$module_srl]->module_srl = $module_srl;
            $rss_config->module_config[$module_srl]->open_rss = $open_rss;

            $oModuleController->insertModuleConfig('rss', $rss_config);

            return new Object();
        }
    }
?>
