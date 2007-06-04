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

    }
?>
