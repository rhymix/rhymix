<?php
    /**
     * @class  moduleView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 View class
     **/

    class moduleView extends module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 모듈 관리자 페이지
         **/
        function dispContent() {
            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 모듈 목록 출력
         **/
        function dispModuleList() {
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('module_list');
        }

    }
?>
