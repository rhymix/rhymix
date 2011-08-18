<?php
    /**
     * @class  importerAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the importer module 
     **/

    class importerAdminView extends importer {

        /**
         * @brief Initialization
         *
         * Importer module is divided by general use and administrative use \n
         **/
        function init() {
        }

        /**
         * @brief Display a form to upload the xml file
         **/
        function dispImporterAdminContent() {
            $this->setTemplatePath($this->module_path.'tpl');

            $source_type = Context::get('source_type');
            switch($source_type) {
                case 'member' : 
                        $template_filename = "member";
                    break;
                case 'ttxml' : 
                        $oModuleModel = &getModel('module');
                        //$mid_list = $oModuleModel->getMidList();	//perhaps mid_list variables not use
                        //Context::set('mid_list', $mid_list);
                        
                        $template_filename = "ttxml";
                    break;
                case 'module' : 
                        $oModuleModel = &getModel('module');
                        //$mid_list = $oModuleModel->getMidList();	//perhaps mid_list variables not use
                        //Context::set('mid_list', $mid_list);
                        
                        $template_filename = "module";
                    break;
                case 'message' : 
                        $template_filename = "message";
                    break;
                case 'sync' : 
                        $template_filename = "sync";
                    break;
                default : 
                        $template_filename = "index";
                    break;
            }

            $this->setTemplateFile($template_filename);
        }

        /**
         * @brief Display a form to upload the xml file
         **/
        function dispImporterAdminImportForm() {
			$oDocumentModel = &getModel('document');	//for document lang use in this page

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }
        
    }
?>
