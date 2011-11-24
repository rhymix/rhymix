<?php
	require_once(_XE_PATH_.'modules/message/message.view.php');

    class messageMobile extends messageView {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Message output
         **/
        function dispMessage() {
            // Get configurations (using module model object)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('message');
            if(!$config->skin) $config->skin = 'default';
            // Set the template path
            $template_path = sprintf('%sm.skins/%s', $this->module_path, $config->skin);
            // Get the member configuration
            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $member_config);
            // Set a flag to check if the https connection is made when using SSL and create https url 
            $ssl_mode = false;
            if($member_config->enable_ssl == 'Y') {
                if(preg_match('/^https:\/\//i',Context::getRequestUri())) $ssl_mode = true;
            }
            Context::set('ssl_mode',$ssl_mode);

            Context::set('system_message', nl2br($this->getMessage()));

			Context::set('act', 'procMemberLogin');
			Context::set('mid', '');

            $this->setTemplatePath($template_path);
            $this->setTemplateFile('system_message');
        }

    }
?>
