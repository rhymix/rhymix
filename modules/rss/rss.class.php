<?php
    /**
     * @class  rss
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     **/

    class rss extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('rss', 'view', 'rss');

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
