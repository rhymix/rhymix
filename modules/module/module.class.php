<?php
    /**
     * @class  module
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 high class
     **/

    class module extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminContent');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminList');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminCategory');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminInfo');

            // module 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/module_info');
            FileHandler::makeDir('./files/cache/triggers');

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
            // 모듈 정보 캐시 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/module_info");

            // 트리거 정보가 있는 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/triggers");

            // DB캐시 파일을 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/db");
        }
    }
?>
