<?php
    /**
     * @class  pluginAdminView
     * @author zero (zero@nzeo.com)
     * @brief  plugin 모듈의 admin view class
     **/

    class pluginAdminView extends plugin {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }
        
        /**
         * @brief 플러그인 목록을 보여줌
         **/
        function dispPluginAdminDownloadedList() {
            // 플러그인 목록을 세팅
            $oPluginModel = &getModel('plugin');
            $plugin_list = $oPluginModel->getDownloadedPluginList();
            Context::set('plugin_list', $plugin_list);

            $this->setTemplateFile('downloaded_plugin_list');
        }
    }
?>
