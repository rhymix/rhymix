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

        /**
         * @brief 내용 직접 입력 위젯 팝업창 내용을 꾸힘
         **/
        function dispWidgetAdminAddContent() {
            $this->setLayoutFile("popup_layout");

            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->stop("msg_invalid_request");

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            Context::set('module_info', $module_info);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'module_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = true;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = false;
            $option->height = 400;
            $option->manual_start = true;
            $editor = $oEditorModel->getEditor($module_srl, $option);
            Context::set('editor', $editor);

            $this->setTemplateFile('add_content_widget');
        }
    }
?>
