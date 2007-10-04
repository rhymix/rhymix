<?php
    /**
     * @class  opage
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 high class
     **/

    class opage extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('opage', 'view', 'dispOpageIndex');
            $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminContent');
            $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminInsert');
            $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminDelete');

            // opage 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/opage');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 외부페이지 모듈을 업그레이드로 설치하였을 경우 필요한 action 값의 여부 체크 (2007. 09. 17)
            if(!$oModuleModel->getActionForward('dispOpageIndex')) return true;
            if(!$oModuleModel->getActionForward('dispOpageAdminContent')) return true;
            if(!$oModuleModel->getActionForward('dispOpageAdminInsert')) return true;
            if(!$oModuleModel->getActionForward('dispOpageAdminDelete')) return true;

            // cache 디렉토리가 없으면 바로 디렉토리 생성
            if(!is_dir('./files/cache/opage')) FileHandler::makeDir('./files/cache/opage');

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 외부페이지 모듈을 업그레이드로 설치하였을 경우 필요한 action 값 등록
            if(!$oModuleModel->getActionForward('dispOpageIndex')) 
                $oModuleController->insertActionForward('opage', 'view', 'dispOpageIndex');

            if(!$oModuleModel->getActionForward('dispOpageAdminContent')) 
                $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminContent');

            if(!$oModuleModel->getActionForward('dispOpageAdminInsert')) 
                $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminInsert');

            if(!$oModuleModel->getActionForward('dispOpageAdminDelete')) 
                $oModuleController->insertActionForward('opage', 'view', 'dispOpageAdminDelete');


            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 외부 페이지 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/opage");
        }
    }
?>
