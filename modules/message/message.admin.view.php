<?php
    /**
     * @class  messageAdminView
     * @author zero (zero@nzeo.com)
     * @brief  message모듈의 admin view class
     **/

    class messageAdminView extends message {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정 
         **/
        function dispMessageAdminConfig() {
            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getskins($this->module_path);
            Context::set('skin_list', $skin_list);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('config');
        }

    }
?>
