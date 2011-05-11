<?php
    /**
     * @class  documentView
     * @author NHN (developers@xpressengine.com)
     * @brief View class of the module document
     **/

    class documentView extends document {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Document printing
         * I make it out to find the geulman;;
         **/
        function dispDocumentPrint() {
            // Bring a list of variables needed to implement
            $document_srl = Context::get('document_srl');

			// module_info not use in UI
            //$oModuleModel = &getModel('module');
            //$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

            // Create the document object. If the document module of basic data structures, write it all works .. -_-;
            $oDocumentModel = &getModel('document');
            // Creates an object for displaying the selected document
            $oDocument = $oDocumentModel->getDocument($document_srl, $this->grant->manager);
            if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');
            // Check permissions
            if(!$oDocument->isAccessible()) return new Object(-1,'msg_not_permitted');
            // Information setting module
            //Context::set('module_info', $module_info);	//module_info not use in UI
            // Browser title settings
            Context::setBrowserTitle($oDocument->getTitleText());
            Context::set('oDocument', $oDocument);

            Context::set('layout','none');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('print_page');
        }

        /**
         * @brief Preview
         **/
        function dispDocumentPreview() {
            Context::set('layout','none');

            $content = Context::get('content');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('preview_page');
        }

        /**
         * @brief Selected by the administrator for the document management
         **/
        function dispDocumentManageDocument() {
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
            // Taken from a list of selected sessions
            $flag_list = $_SESSION['document_management'];
            if(count($flag_list)) {
                foreach($flag_list as $key => $val) {
                    if(!is_bool($val)) continue;
                    $document_srl_list[] = $key;
                }
            }

            if(count($document_srl_list)) {
                $oDocumentModel = &getModel('document');
                $document_list = $oDocumentModel->getDocuments($document_srl_list, $this->grant->is_admin);
                Context::set('document_list', $document_list);
            }

            $oModuleModel = &getModel('module');
            // The combination of module categories list and the list of modules
            if(count($module_list)>1) Context::set('module_list', $module_categories);
            // Select Pop-up layout
            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('popup_layout');

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('checked_list');
        }

        function triggerDispDocumentAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // Get information of the current module
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            $oModuleModel = &getModel('module');
            if($current_module_srl)
            {
                $document_config = $oModuleModel->getModulePartConfig('document', $current_module_srl);
            }
            if(!isset($document_config->use_history)) $document_config->use_history = 'N';
            Context::set('document_config', $document_config);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'document_module_config');
            $obj .= $tpl;
            
            return new Object();
        }

    }
?>
