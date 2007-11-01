<?php
    /**
     * @class  trackbackAdminController
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 admin controller class
     **/

    class trackbackAdminController extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 엮인글들을 삭제
         **/
        function procTrackbackAdminDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $trackback_srl_list= explode('|@|', $cart);
            $trackback_count = count($trackback_srl_list);
            if(!$trackback_count) return $this->stop('msg_cart_is_null');

            $oTrackbackController = &getController('trackback');

            // 글삭제
            for($i=0;$i<$trackback_count;$i++) {
                $trackback_srl = trim($trackback_srl_list[$i]);
                if(!$trackback_srl) continue;

                $oTrackbackController->deleteTrackback($trackback_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_trackback_is_deleted'), $trackback_count) );
        }

        /**
         * @brief 설정 저장
         **/
        function procTrackbackAdminInsertConfig() {
            // 기존 설정을 가져옴 
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('trackback');

            $config->enable_trackback = Context::get('enable_trackback');
            if($config->enable_trackback != 'Y') $config->enable_trackback = 'N';

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('trackback',$config);
            return $output;
        }

        /**
         * @brief Trackback 모듈별 설정
         **/
        function procTrackbackAdminInsertModuleConfig() {
            // 필요한 변수를 받아옴
            $module_srl = Context::get('target_module_srl');
            $enable_trackback = Context::get('enable_trackback');
            if(!in_array($enable_trackback, array('Y','N'))) $enable_trackback = 'N';
            
            if(!$module_srl || !$enable_trackback) return new Object(-1, 'msg_invalid_request');

            // 설정 저장
            $output = $this->setTrackbackModuleConfig($module_srl, $enable_trackback);

            $this->setMessage('success_registed');
        }

        /**
         * @brief Trackback 모듈별 설정 함수
         **/
        function setTrackbackModuleConfig($module_srl, $enable_trackback) {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            $trackback_config = $oModuleModel->getModuleConfig('trackback');
            $trackback_config->module_config[$module_srl]->module_srl = $module_srl;
            $trackback_config->module_config[$module_srl]->enable_trackback = $enable_trackback;

            $oModuleController->insertModuleConfig('trackback', $trackback_config);

            return new Object();
        }

        /**
         * @brief 모듈에 속한 모든 트랙백 삭제
         **/
        function deleteModuleTrackbacks($module_srl) {
            // 삭제
            $args->module_srl = $module_srl;
            $output = executeQuery('trackback.deleteModuleTrackbacks', $args);

            return $output;
        }
    }
?>
