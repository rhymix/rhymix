<?php
    /**
     * @class  addon
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 high class
     **/

    class addon extends ModuleObject {

        var $cache_file = "./files/cache/activated_addons.cache.php";

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('addon', 'view', 'dispAddonAdminIndex');
            
            // 몇가지 애드온을 기본으로 설치 상태로 지정
            $oAddonController = &getAdminController('addon');
            $oAddonController->doActivate('spamfilter');
            $oAddonController->doActivate('message');
            $oAddonController->doActivate('member_extra_info');
            $oAddonController->doActivate('counter');
            $oAddonController->procAddonAdminToggleActivate();
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
