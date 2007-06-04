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
            $this->setTemplateFile('index');
        }
        
    }
?>
