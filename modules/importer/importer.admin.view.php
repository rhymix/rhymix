<?php
    /**
     * @class  importerAdminView
     * @author zero (zero@nzeo.com)
     * @brief  importer 모듈의 admin view class
     **/

    class importerAdminView extends importer {

        /**
         * @brief 초기화
         *
         * importer 모듈은 일반 사용과 관리자용으로 나누어진다.\n
         **/
        function init() {
        }

        /**
         * @brief XML 파일을 업로드하는 form 출력
         **/
        function dispImporterAdminContent() {
            $this->setTemplatePath($this->module_path.'tpl');

            $source_type = Context::get('source_type');
            switch($source_type) {
                case 'member' : 
                        $template_filename = "member";
                    break;
                case 'module' : 
                        // 전체 모듈 목록 구함
                        $oModuleModel = &getModel('module');
                        $mid_list = $oModuleModel->getMidList();
                        Context::set('mid_list', $mid_list);
                        
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
        
    }
?>
