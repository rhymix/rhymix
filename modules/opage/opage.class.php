<?php
    /**
     * @class  opage
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 high class
     **/

    class opage extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // opage 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/opage');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            // cache 디렉토리가 없으면 바로 디렉토리 생성
            if(!is_dir('./files/cache/opage')) FileHandler::makeDir('./files/cache/opage');

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 외부 페이지 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/opage");
        }
    }
?>
