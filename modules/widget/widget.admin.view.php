<?php
    /**
     * @class  widgetAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class for widget modules
     **/

    class widgetAdminView extends widget {

        /**
         * @brief Initialization
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }
        
        /**
         * @brief Showing a list of widgets
         **/
        function dispWidgetAdminDownloadedList() {
            // Set widget list
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            $this->setTemplateFile('downloaded_widget_list');
        }

        /**
         * @brief For information on direct entry widget popup kkuhim
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
            // Editors settings of the module by calling getEditor
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getModuleEditor('document',$module_srl, $module_srl,'module_srl','content');
            Context::set('editor', $editor);

            $this->setLayoutFile("popup_layout");
            $this->setTemplateFile('add_content_widget');

        }

    }
?>
