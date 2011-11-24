<?php
    /**
     * @class  messageAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the message module
     **/

    class messageAdminView extends message {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Configuration
         **/
        function dispMessageAdminConfig() {
            // Get a list of skins(themes)
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getskins($this->module_path);
            Context::set('skin_list', $skin_list);
            // Get configurations (using module model object)
            $config = $oModuleModel->getModuleConfig('message');
            Context::set('config',$config);
            // Set a template file
            $this->setTemplatePath($this->module_path.'tpl');
			
			//Security
			$security = new Security();
			$security->encodeHTML('skin_list..title');				
			
            $this->setTemplateFile('config');
        }

    }
?>
