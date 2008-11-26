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
            
            // 몇가지 애드온을 등록
            $oAddonController = &getAdminController('addon');
            $oAddonController->doInsert('blogapi');
            $oAddonController->doInsert('counter');
            $oAddonController->doInsert('member_extra_info');
            $oAddonController->doInsert('openid_delegation_id');
            $oAddonController->doInsert('rainbow_link');
            $oAddonController->doInsert('point_level_icon');
            $oAddonController->doInsert('referer');

            // 몇가지 애드온을 기본 활성화 상태로 변경
            $oAddonController->doActivate('autolink');
            $oAddonController->doActivate('counter');
            $oAddonController->doActivate('member_communication');
            $oAddonController->doActivate('member_extra_info');
            $oAddonController->doActivate('mobile');
            $oAddonController->doActivate('referer');
            $oAddonController->doActivate('resize_image');
            $oAddonController->procAddonAdminToggleActivate();
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
            $oAddonController = &getAdminController('addon');
            $oAddonController->makeCacheFile();
        }

    }
?>
