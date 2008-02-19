<?php
    /**
     * @class  widget
     * @author zero (zero@nzeo.com)
     * @brief  widget 모듈의 high class
     **/

    class widget extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetInfo');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetGenerateCode');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetGenerateCodePage');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetAdminDownloadedList');

            // widget 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/widget');
			FileHandler::makeDir('./files/cache/widget_cache');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // widget 정보를 담은 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/widget");

            // widget 생성 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/widget_cache");
        }

        /**
         * @brief 권한 체크를 실행하는 method
         * 모듈 객체가 생성된 경우는 직접 권한을 체크하지만 기능성 모듈등 스스로 객체를 생성하지 않는 모듈들의 경우에는
         * ModuleObject에서 직접 method를 호출하여 권한을 확인함
         *
         * isAdminGrant는 관리권한 이양시에만 사용되도록 하고 기본은 false로 return 되도록 하여 잘못된 권한 취약점이 생기지 않도록 주의하여야 함
         **/
        function isAdmin() {
            // 로그인이 되어 있지 않으면 무조건 return false
            $is_logged = Context::get('is_logged');
            if(!$is_logged) return false;

            // 사용자 아이디를 구함
            $logged_info = Context::get('logged_info');

            // 모듈 요청에 사용된 변수들을 가져옴
            $args = Context::getRequestVars();

            // act의 값에 따라서 관리 권한 체크
            switch($args->act) {
                case 'dispWidgetAdminAddContent' :
                        // 레이아웃 정보에 할당된 srl이 없으면 패스
                        if(!$args->module_srl) return false;

                        // 모듈중 레이아웃이 해당 srl에 연결될 것이 있는지 확인
                        $oModuleModel = &getModel('module');
                        $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                        if($oModuleModel->isModuleAdmin($module_info,$logged_info)) $is_granted = true;
                        else $is_granted = false;
                        return $is_granted;
                    break;
            }

            return false;
        }
    }
?>
