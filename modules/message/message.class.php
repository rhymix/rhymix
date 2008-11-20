<?php
    /**
     * @class  message
     * @author zero (zero@nzeo.com)
     * @brief  message모듈의 high class
     **/

    class message extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('message', 'view', 'dispMessage');
            $oModuleController->insertActionForward('message', 'view', 'dispMessageAdminConfig');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');
            if(!$oModuleModel->getActionForward('dispMessage')) return true;
            if(!$oModuleModel->getActionForward('dispMessageAdminConfig')) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            if(!$oModuleModel->getActionForward('dispMessage')) 
                $oModuleController->insertActionForward('message', 'view', 'dispMessage');
            if(!$oModuleModel->getActionForward('dispMessageAdminConfig')) 
                $oModuleController->insertActionForward('message', 'view', 'dispMessageAdminConfig');
            return new Object();
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
