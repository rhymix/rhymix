<?php
    /**
     * @class  ttimporterAdminView
     * @author zero (zero@nzeo.com)
     * @brief  ttimporter 모듈의 admin view class
     **/

    class ttimporterAdminView extends ttimporter {

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
        function dispTtimporterAdminContent() {
            // 모듈 목록을 구함
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getMidList();
            Context::set('module_list', $module_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }
        
    }
?>
