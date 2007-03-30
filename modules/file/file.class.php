<?php
    /**
     * @class  file
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 high 클래스
     **/

    class file extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('file', 'view', 'dispFileAdminList');
            $oModuleController->insertActionForward('file', 'controller', 'dispFileDownload');
            $oModuleController->insertActionForward('file', 'controller', 'dispFileAdminDeleteChecked');

            // file 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/attach/images');
            FileHandler::makeDir('./files/attach/binaries');

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
