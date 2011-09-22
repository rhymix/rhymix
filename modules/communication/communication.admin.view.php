<?php
    /**
     * @class  communicationAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief communication module of the admin view class
     **/

    class communicationAdminView extends communication {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief configuration to manage messages and friends
         **/
        function dispCommunicationAdminConfig() {
            // Creating an object
            $oEditorModel = &getModel('editor');
            $oModuleModel = &getModel('module');
            $oCommunicationModel = &getModel('communication');
            // get the configurations of communication module
            Context::set('communication_config', $oCommunicationModel->getConfig() );
            // get a list of editor skins
            Context::set('editor_skin_list', $oEditorModel->getEditorSkinList() );
            // get a list of communication skins
            Context::set('communication_skin_list', $oModuleModel->getSkins($this->module_path) );
			$security = new Security();		
			$security->encodeHTML('communication_config..');
			$security->encodeHTML('editor_skin_list..');
			$security->encodeHTML('communication_skin_list..title');			

			// specify a template
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }

    }
?>
