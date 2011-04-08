<?php
    /**
     * @class  trackbackView
     * @author NHN (developers@xpressengine.com)
     * @brief trackback module's view class
     **/

    class trackbackView extends trackback {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Display output list (administrative)
         **/
        function dispTrackbackSend() {
            $document_srl = Context::get('document_srl');
            if(!$document_srl) return $this->stop('msg_invalid_request');

            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return $this->stop('msg_not_permitted');
            // Wanted Original article information
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return $this->stop('msg_invalid_document');
            if($oDocument->isSecret()) return $this->stop('msg_invalid_request');

            if($oDocument->getMemberSrl() != $logged_info->member_srl) return $this->stop('msg_not_permitted');

            Context::set('oDocument', $oDocument);
            // Set a template
            $this->setLayoutFile('popup_layout');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('send_trackback_form');
        }

        /**
         * @brief An additional set of parts for a service module
         * Use the form out of the settings for trackback
         **/
        function triggerDispTrackbackAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // Get information of the current module
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }
            // Imported trackback settings of the selected module
            $oTrackbackModel = &getModel('trackback');
            $trackback_config = $oTrackbackModel->getTrackbackModuleConfig($current_module_srl);
            Context::set('trackback_config', $trackback_config);
            // Set a template file
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'trackback_module_config');
            $obj .= $tpl;

            return new Object();
        }
    }
?>
