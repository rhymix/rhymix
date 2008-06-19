<?php
    /**
     * @class  communicationAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 admin model class
     **/

    class communicationAdminModel extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 지정된 스킨의 컬러셋 선택을 위한 html을 return
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

                $oTemplate = &TemplateHandler::getInstance();
                $tpl = $oTemplate->compile($this->module_path.'tpl', 'colorset_list');
            }

            $this->add('tpl', $tpl);
        }

    }
?>
