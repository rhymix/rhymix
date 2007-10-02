<?php
    /**
     * @class  trackback
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 high class
     **/

    class trackback extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('trackback', 'controller', 'trackback');
            $oModuleController->insertActionForward('trackback', 'view', 'dispTrackbackAdminList');

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
        }
    }
?>
