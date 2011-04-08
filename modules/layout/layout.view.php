<?php
    /**
     * @class  layoutView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the layout module
     **/

    class layoutView extends layout {

        /**
         * @brief Initialization
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief Pop-up layout details(conf/info.xml)
         **/
        function dispLayoutInfo() {
            // Get the layout information
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            if(!$layout_info) exit();
            Context::set('layout_info', $layout_info);
            // Set the layout to be pop-up
            $this->setLayoutFile('popup_layout');
            // Set a template file
            $this->setTemplateFile('layout_detail_info');
        }
    }
?>
