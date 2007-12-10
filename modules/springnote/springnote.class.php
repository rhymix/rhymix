<?php
    /**
     * @class  springnote
     * @author zero (zero@nzeo.com)
     * @brief  springnote 모듈의 high 클래스
     **/

    class springnote extends ModuleObject {
        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteContent');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminContent');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminSpringnoteInfo');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminInsertSpringnote');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminDeleteSpringnote');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminGrantInfo');
            $oModuleController->insertActionForward('springnote', 'view', 'dispSpringnoteAdminSkinInfo');
            $oModuleController->insertActionForward('springnote', 'controller', 'procSpringnoteAdminInsertSpringnote');
            $oModuleController->insertActionForward('springnote', 'controller', 'procSpringnoteAdminDeleteSpringnote');
            $oModuleController->insertActionForward('springnote', 'controller', 'procSpringnoteAdminInsertGrant');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getActionForward('dispSpringnoteContent')) return true;

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
