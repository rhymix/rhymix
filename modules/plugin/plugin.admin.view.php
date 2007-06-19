<?php
    /**
     * @class  widgetAdminView
     * @author zero (zero@nzeo.com)
     * @brief  widget 모듈의 admin view class
     **/

    class widgetAdminView extends widget {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }
        
        /**
         * @brief 위젯 목록을 보여줌
         **/
        function dispWidgetAdminDownloadedList() {
            // 위젯 목록을 세팅
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            $this->setTemplateFile('downloaded_widget_list');
        }
    }
?>
