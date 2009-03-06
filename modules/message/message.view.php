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
            $config = $oModuleModel->getModuleConfig('message');
            if(!$config->skin) $config->skin = 'default';

            // 템플릿 경로를 지정
            $template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);

            // 회원 관리 정보를 받음
            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $member_config);

            // ssl 사용시 현재 https접속상태인지에 대한 flag및 https url 생성
            $ssl_mode = false;
            if($member_config->enable_ssl == 'Y') {
                if(preg_match('/^https:\/\//i',Context::getRequestUri())) $ssl_mode = true;
            }
            Context::set('ssl_mode',$ssl_mode);

            Context::set('system_message', $this->getMessage());

            $this->setTemplatePath($template_path);
            $this->setTemplateFile('system_message');
        }

    }
?>
