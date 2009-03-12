<?php
    /**
     * @class  addonAdminController
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 admin controller class
     **/
    include_once('addon.controller.php');

    class addonAdminController extends addonController {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 애드온의 활성/비활성 체인지
         **/
        function procAddonAdminToggleActivate() {
            $oAddonModel = &getAdminModel('addon');

            $site_module_info = Context::get('site_module_info');

            // addon값을 받아옴
            $addon = Context::get('addon');
            if($addon) {
                // 활성화 되어 있으면 비활성화 시킴
                if($oAddonModel->isActivatedAddon($addon, $site_module_info->site_srl)) $this->doDeactivate($addon, $site_module_info->site_srl);

                // 비활성화 되어 있으면 활성화 시킴
                else $this->doActivate($addon, $site_module_info->site_srl);
            }

            $this->makeCacheFile($site_module_info->site_srl);
        }

        /**
         * @brief 애드온 설정 정보 입력
         **/
        function procAddonAdminSetupAddon() {
            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            unset($args->module);
            unset($args->act);
            unset($args->addon_name);
            unset($args->body);

            $site_module_info = Context::get('site_module_info');

            $this->doSetup($addon_name, $args, $site_module_info->site_srl);

            $this->makeCacheFile($site_module_info->site_srl);
        }



        /**
         * @brief 애드온 추가
         * DB에 애드온을 추가함
         **/
        function doInsert($addon, $site_srl = 0) {
            $args->addon = $addon;
            $args->is_used = 'N';
            if(!$site_srl) return executeQuery('addon.insertAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.insertSiteAddon', $args);
        }

        /**
         * @brief 애드온 활성화 
         * addons라는 테이블에 애드온의 활성화 상태를 on 시켜줌
         **/
        function doActivate($addon, $site_srl = 0) {
            $args->addon = $addon;
            $args->is_used = 'Y';
            if(!$site_srl) return executeQuery('addon.updateAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.updateSiteAddon', $args);
        }

        /**
         * @brief 애드온 비활성화 
         *
         * addons라는 테이블에 애드온의 이름을 제거하는 것으로 비활성화를 시키게 된다
         **/
        function doDeactivate($addon, $site_srl = 0) {
            $args->addon = $addon;
            $args->is_used = 'N';
            if(!$site_srl) return executeQuery('addon.updateAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.updateSiteAddon', $args);
        }


    }
?>
