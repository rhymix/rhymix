<?php
    /**
     * @class  pagemaker
     * @author zero (zero@nzeo.com)
     * @brief  pagemaker 모듈의 high class
     **/

    class pagemaker extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // plugin 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/page');

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
