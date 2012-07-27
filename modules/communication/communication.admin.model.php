<?php
    /**
     * @class  communicationAdminModel
     * @author NHN (developers@xpressengine.com)
     * @brief communication module of the admin model class
     **/

    class communicationAdminModel extends communication {

        /**
         * Initialization
         **/
        function init() {
        }

        /**
         * the html to select colorset of the skin
		 * @return void
         **/
        function getCommunicationAdminColorset() {
            $skin = Context::get('skin');
            if(!$skin) $tpl = "";
            else {
                $oModuleModel = &getModel('module');
                $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);
                Context::set('skin_info', $skin_info);

                $oModuleModel = &getModel('module');
                $communication_config = $oModuleModel->getModuleConfig('communication');
                if(!$communication_config->colorset) $communication_config->colorset = "white";
                Context::set('communication_config', $communication_config);
				
				$security = new Security();
				$security->encodeHTML('skin_info.colorset..title','skin_info.colorset..name');
				$security->encodeHTML('skin_info.colorset..name');

                $oTemplate = &TemplateHandler::getInstance();
                $tpl = $oTemplate->compile($this->module_path.'tpl', 'colorset_list');
            }

            $this->add('tpl', $tpl);
        }

    }
?>
