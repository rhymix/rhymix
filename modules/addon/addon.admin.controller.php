<?php
    /**
     * @class  addonAdminController
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 admin controller class
     **/

    class addonAdminController extends addon {

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

            // addon값을 받아옴
            $addon = Context::get('addon');
            if($addon) {
                // 활성화 되어 있으면 비활성화 시킴
                if($oAddonModel->isActivatedAddon($addon)) $this->doDeactivate($addon);

                // 비활성화 되어 있으면 활성화 시킴
                else $this->doActivate($addon);
            }

            // 모듈에서 애드온을 사용하기 위한 캐시 파일 생성
            $buff = "";
            $addon_list = $oAddonModel->getInsertedAddons();
            foreach($addon_list as $addon=> $val) {
                if($val->is_used != 'Y') continue;

                if($val->extra_vars) {
                    unset($extra_vars);
                    $extra_vars = base64_encode($val->extra_vars);
                }

                $buff .= sprintf(' if(file_exists("./addons/%s/%s.addon.php")) { unset($addon_info); $addon_info = unserialize(base64_decode("%s")); $addon_path = "./addons/%s/"; @include("./addons/%s/%s.addon.php"); }', $addon, $addon, $extra_vars, $addon, $addon, $addon);
            }

            $buff = sprintf('<?if(!defined("__ZBXE__"))exit(); %s ?>', $buff);

            FileHandler::writeFile($this->cache_file, $buff);
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

            $this->doSetup($addon_name, $args);
        }

        /**
         * @brief 애드온 추가
         * DB에 애드온을 추가함
         **/
        function doInsert($addon) {
            $args->addon = $addon;
            $args->is_used = 'N';
            return executeQuery('addon.insertAddon', $args);
        }

        /**
         * @brief 애드온 활성화 
         * addons라는 테이블에 애드온의 활성화 상태를 on 시켜줌
         **/
        function doActivate($addon) {
            $args->addon = $addon;
            $args->is_used = 'Y';
            return executeQuery('addon.updateAddon', $args);
        }

        /**
         * @brief 애드온 비활성화 
         *
         * addons라는 테이블에 애드온의 이름을 제거하는 것으로 비활성화를 시키게 된다
         **/
        function doDeactivate($addon) {
            $args->addon = $addon;
            $args->is_used = 'N';
            return executeQuery('addon.updateAddon', $args);
        }

        /**
         * @brief 애드온 설정
         **/
        function doSetup($addon, $extra_vars) {
            $args->addon = $addon;
            $args->extra_vars = serialize($extra_vars);
            return executeQuery('addon.updateAddon', $args);
        }
    }
?>
