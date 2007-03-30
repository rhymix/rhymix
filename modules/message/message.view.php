<?php
    /**
     * @class  messageView
     * @author zero (zero@nzeo.com)
     * @brief  message모듈의 view class
     **/

    class messageView extends message {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 메세지 출력 
         **/
        function dispMessage() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('rss');
            if(!$config->skin) $config->skin = 'default';

            // 템플릿 경로를 지정
            $template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);

            Context::set('system_message', $this->getMessage());

            $this->setTemplatePath($template_path);
            $this->setTemplateFile('system_message');
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
            $this->setTemplatePath($this->module_path.'tpl.admin/');
            $this->setTemplateFile('config');
        }

    }
?>
