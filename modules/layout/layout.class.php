<?php
    /**
     * @class  layout
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 high class
     **/

    class layout extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('layout', 'view', 'dispLayoutAdminContent');
            $oModuleController->insertActionForward('layout', 'view', 'dispLayoutAdminInsertLayout');
            $oModuleController->insertActionForward('layout', 'view', 'dispLayoutAdminMenu');
            $oModuleController->insertActionForward('layout', 'view', 'dispLayoutAdminDownloadedLayoutList');
            $oModuleController->insertActionForward('layout', 'view', 'dispLayoutAdminAdminInfo');
            
            $oModuleController->insertActionForward('layout', 'model', 'getLayoutAdminMenuSrl');
            $oModuleController->insertActionForward('layout', 'model', 'getLayoutAdminMenuTplInfo');

            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminInsert');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminUpdate');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminDelete');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminInsertMenu');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminDeleteMenu');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminMakeXmlFile');
            $oModuleController->insertActionForward('layout', 'controller', 'procLayoutAdminMoveMenu');

            // 레이아웃에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/layout');

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
