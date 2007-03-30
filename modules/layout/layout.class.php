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
            $oModuleController->insertActionFoward('layout', 'view', 'dispLayoutAdminContent');
            $oModuleController->insertActionFoward('layout', 'view', 'dispLayoutAdminInsertLayout');
            $oModuleController->insertActionFoward('layout', 'view', 'dispLayoutAdminMenu');
            $oModuleController->insertActionFoward('layout', 'view', 'dispLayoutAdminDownloadedLayoutList');
            $oModuleController->insertActionFoward('layout', 'view', 'dispLayoutAdminAdminInfo');
            
            $oModuleController->insertActionFoward('layout', 'model', 'getLayoutAdminMenuSrl');
            $oModuleController->insertActionFoward('layout', 'model', 'getLayoutAdminMenuTplInfo');

            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminInsert');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminUpdate');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminDelete');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminInsertMenu');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminDeleteMenu');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminMakeXmlFile');
            $oModuleController->insertActionFoward('layout', 'controller', 'procLayoutAdminMoveMenu');

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
