<?php
    /**
     * @class  sessionAdminView
     * @author zero (zero@nzeo.com)
     * @brief  session모듈의 admin view class
     **/

    class sessionAdminView extends session {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정 
         **/
        function dispSessionAdminIndex() {
            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }

    }
?>
