<?php
    /**
     * @class  moduleView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 View class
     **/

    class moduleView extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈 목록을 출력
         **/
        function dispContent() {
            // 모듈모델 객체를 구함
            $oModuleModel = getModule('module','model');

            // 등록된 모듈의 목록을 구해옴
            $installed_module_list = $oModuleModel->getModulesInfo();
            Context::set('installed_module_list', $installed_module_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

    }
?>
