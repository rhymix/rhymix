<?php
    /**
     * @class wiki
     * @author haneul (haneul0318@gmail.com)
     * @brief  wiki 모듈의 high class
     **/

    class wiki extends ModuleObject {
        function moduleInstall() {
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            return false;
        }

        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
