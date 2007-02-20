<?php
    /**
     * @class  installView
     * @author zero (zero@nzeo.com)
     * @brief  install module의 View class
     **/

    class installView extends install {

        /**
         * @brief 초기화
         **/
        function init() {
            // template 경로를 지정
            $this->setTemplatePath($this->module_path."tpl");

            // 컨트롤러 생성
            $oController = &getController('install');

            // 설치 불가능하다면 introduce를 출력
            if(!$oController->checkInstallEnv()) $this->act = $this->default_act;

            // 설치 가능한 환경이라면 installController::makeDefaultDirectory() 실행
            else $oController->makeDefaultDirectory();
        }

        /**
         * @brief license 및 설치 환경에 대한 메세지 보여줌
         **/
        function dispIntroduce() {
            $this->setTemplateFile('introduce');
        }

        /**
         * @brief DB 정보/ 최고 관리자 정보 입력 화면을 보여줌
         **/
        function dispInstallForm() {
            // db_type이 지정되지 않았다면 다시 초기화면 출력
            if(!Context::get('db_type')) return $this->viewIntroduce();

            // disp_db_info_form.html 파일 출력
            $tpl_filename = sprintf('form.%s', Context::get('db_type'));
            $this->setTemplateFile($tpl_filename);
        }

    }
?>
