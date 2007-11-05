<?php
    /**
     * @class  widget
     * @author zero (zero@nzeo.com)
     * @brief  widget 모듈의 high class
     **/

    class widget extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetInfo');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetGenerateCode');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetGenerateCodePage');
            $oModuleController->insertActionForward('widget', 'view', 'dispWidgetAdminDownloadedList');

            // widget 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/widget');
			FileHandler::makeDir('./files/cache/widget_cache');

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
            // widget 정보를 담은 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/widget");

            // widget 생성 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/widget_cache");
        }
    }
?>
