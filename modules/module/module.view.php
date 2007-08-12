<?php
    /**
     * @class  moduleView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 view class
     **/

    class moduleView extends module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 스킨 정보 출력
         **/
        function dispModuleSkinInfo() {
            $selected_module = Context::get('selected_module');
            $skin = Context::get('skin');

            // 모듈/스킨 정보를 구함
            $module_path = sprintf("./modules/%s/", $selected_module);
            if(!is_dir($module_path)) $this->stop("msg_invalid_request");

            $skin_info_xml = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
            if(!file_exists($skin_info_xml)) $this->stop("msg_invalid_request");

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
            Context::set('skin_info',$skin_info);

            $this->setLayoutFile("popup_layout");
            $this->setTemplateFile("skin_info");
        }

    }
?>
