<?php
    /**
     * @class  page
     * @author zero (zero@nzeo.com)
     * @brief  page 모듈의 high class
     **/

    class page extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('page', 'view', 'dispPageIndex');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminContent');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminModuleConfig');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminInfo');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminInsert');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminDelete');

            // page 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/page');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
