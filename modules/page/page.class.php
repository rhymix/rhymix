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
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminInfo');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminInsert');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminDelete');
            $oModuleController->insertActionForward('page', 'view', 'dispPageAdminContentModify');

            // page 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/page');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getActionForward('dispPageAdminContentModify')) return true;
            if(!$oModuleModel->getActionForward('dispPageAdminInfo')) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            if(!$oModuleModel->getActionForward('dispPageAdminContentModify')) 
                $oModuleController->insertActionForward('page', 'view', 'dispPageAdminContentModify');
            if(!$oModuleModel->getActionForward('dispPageAdminInfo')) 
                $oModuleController->insertActionForward('page', 'view', 'dispPageAdminInfo');

            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 페이지 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/page");
        }

        /**
         * @brief Action중 Admin이 들어갔을 경우 권한 체크
         **/
        function checkAdminActionGrant() {
            if(!Context::get('is_logged')) return false;

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin=='Y') return true;

            $actions = array('procPageAdminRemoveWidgetCache','dispPageAdminContentModify','procPageAdminInsertContent');
            if(!in_array($this->act, $actions)) return false;

            $oModuleModel = &getModel('module');
            if($oModuleModel->isSiteAdmin()) return true;

            return false;
        }
    }
?>
