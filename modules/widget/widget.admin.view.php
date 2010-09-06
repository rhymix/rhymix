<?php
    /**
     * @class  widgetAdminView
     * @author NHN (developers@xpressengine.com)
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
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->stop("msg_invalid_request");

            $document_srl = Context::get('document_srl');
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            Context::set('oDocument', $oDocument);

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            Context::set('module_info', $module_info);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getModuleEditor('document',$module_srl, $module_srl,'module_srl','content');
            Context::set('editor', $editor);

            $this->setLayoutFile("popup_layout");
            $this->setTemplateFile('add_content_widget');

        }

    }
?>
