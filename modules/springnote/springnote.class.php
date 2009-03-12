<?php
    /**
     * @class  springnote
     * @author zero (zero@nzeo.com)
     * @brief  springnote 모듈의 high 클래스
     **/

    class springnote extends ModuleObject {
        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
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
            $this->moduleInstall();
            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }

?>
