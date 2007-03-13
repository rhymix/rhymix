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
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('rss');
            if(!$config->skin) $config->skin = 'default';
            Context::set('skin',$config->skin);

            // 템플릿 경로를 지정
            $template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 메세지 출력 
         **/
        function dispContent() {
            Context::set('system_message', $this->getMessage());
            $this->setTemplateFile('system_message');
        }

        /**
         * @brief 설정 
         **/
        function dispConfig() {
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
