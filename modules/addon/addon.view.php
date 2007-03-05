<?php
    /**
     * @class  addonView
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 View class
     **/

    class addonView extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 애드온 목록을 보여줌
         **/
        function dispAddonList() {
            // 애드온 목록을 세팅
            $oAddonModel = &getModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            $this->setTemplateFile('addon_list');
        }


    }
?>
