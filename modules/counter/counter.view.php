<?php
    /**
     * @class  counterView
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 View class
     **/

    class counterView extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 관리자 페이지 초기화면
         **/
        function dispCounterAdminIndex() {
            $this->setTemplateFile('index');
        }

    }
?>
