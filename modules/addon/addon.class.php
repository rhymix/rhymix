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
            // 몇가지 애드온을 등록
            $oAddonController = &getAdminController('addon');
            $oAddonController->doInsert('autolink');
            $oAddonController->doInsert('blogapi');
            $oAddonController->doInsert('counter');
            $oAddonController->doInsert('member_communication');
            $oAddonController->doInsert('member_extra_info');
            $oAddonController->doInsert('mobile');
            $oAddonController->doInsert('referer');
            $oAddonController->doInsert('resize_image');
            $oAddonController->doInsert('openid_delegation_id');
            $oAddonController->doInsert('rainbow_link');
            $oAddonController->doInsert('point_level_icon');

            // 몇가지 애드온을 기본 활성화 상태로 변경
            $oAddonController->doActivate('autolink');
            $oAddonController->doActivate('counter');
            $oAddonController->doActivate('member_communication');
            $oAddonController->doActivate('member_extra_info');
            $oAddonController->doActivate('mobile');
            $oAddonController->doActivate('referer');
            $oAddonController->doActivate('resize_image');
            $oAddonController->makeCacheFile(0);
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            if(file_exists($this->cache_file)) FileHandler::removeFile($this->cache_file);
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
            FileHandler::removeFilesInDir('./files/cache/addons');
        }

    }
?>
