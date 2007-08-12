<?php
    /**
     * @class  integration_search
     * @author zero (zero@nzeo.com)
     * @brief  integration_search module의 view class
     **/

    class integration_search extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('integration_search', 'view', 'IS');
            $oModuleController->insertActionForward('integration_search', 'view', 'dispIntegration_searchAdminContent');
            $oModuleController->insertActionForward('integration_search', 'view', 'dispIntegration_searchAdminSkinInfo');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            // IS act의 여부 체크 (2007. 7. 24 추가)
            $oModuleModel = &getModel('module');
            $act = $oModuleModel->getActionForward('dispIntegration_searchAdminSkinInfo');
            if(!$act) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('integration_search', 'view', 'IS');
            $oModuleController->insertActionForward('integration_search', 'view', 'dispIntegration_searchAdminContent');
            $oModuleController->insertActionForward('integration_search', 'view', 'dispIntegration_searchAdminSkinInfo');
            return new Object(0, 'success_updated');
        }

    }
?>
