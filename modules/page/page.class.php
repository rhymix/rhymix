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
            // page 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/page');

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
            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 페이지 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/page");
        }
    }
?>
