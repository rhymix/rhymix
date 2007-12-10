<?php
    /**
     * @class  lifepod
     * @author zero (zero@nzeo.com)
     * @brief  lifepod 모듈의 high 클래스
     **/

    class lifepod extends ModuleObject {
        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodContent');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminContent');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminLifepodInfo');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminInsertLifepod');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminDeleteLifepod');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminGrantInfo');
            $oModuleController->insertActionForward('lifepod', 'view', 'dispLifepodAdminSkinInfo');
            $oModuleController->insertActionForward('lifepod', 'controller', 'procLifepodAdminInsertLifepod');
            $oModuleController->insertActionForward('lifepod', 'controller', 'procLifepodAdminDeleteLifepod');
            $oModuleController->insertActionForward('lifepod', 'controller', 'procLifepodAdminInsertGrant');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getActionForward('dispLifepodContent')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $this->moduleInstall();
            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }

?>
