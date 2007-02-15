<?php
    /**
     * @file   installView
     * @author zero (zero@nzeo.com)
     * @brief  기본 모듈중의 하나인 install module의 View
     **/

    class installView extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template 경로를 지정
            $this->setTemplatePath($this->module_path."tpl");

            // 컨트롤러 생성
            $oController = getModule('install','controller');

            // 설치 불가능하다면 introduce를 출력
            if(!$oController->checkInstallEnv()) $this->act = $this->default_act;

            // 설치 가능한 환경이라면 installController::makeDefaultDirectory() 실행
            else $oController->makeDefaultDirectory();
        }

        /**
         * @brief license 및 설치 환경에 대한 메세지 보여줌
         **/
        function viewIntroduce() {
            $this->setTemplateFile('introduce');
        }

        /**
         * @brief DB 정보 입력 화면을 보여줌
         **/
        function viewDBInfoForm() {
            // db_type이 지정되지 않았다면 다시 초기화면 출력
            if(!Context::get('db_type')) return $this->viewIntroduce();

            // disp_db_info_form.html 파일 출력
            $tpl_filename = sprintf('form.%s', Context::get('db_type'));
            $this->setTemplateFile($tpl_filename);
        }

    }
?>
