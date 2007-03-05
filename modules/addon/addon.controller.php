<?php
    /**
     * @class  addonController
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 Controller class
     **/

    class addonController extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 애드온의 활성/비활성 체인지
         **/
        function procToggleActivateAddon() {
            // addon값을 받아옴
            $addon = Context::get('addon');
            if($addon) {
                $oAddonModel = &getModel('addon');

                // 활성화 되어 있으면 비활성화 시킴
                if($oAddonModel->isActivatedAddon($addon)) $this->doDeactivate($addon);

                // 비활성화 되어 있으면 활성화 시킴
                else $this->doActivate($addon);
            }

            // 페이지를 애드온 목록으로 이동
            $this->setRedirectUrl("./?module=admin&act=dispAddonList");
        }

        /**
         * @brief 애드온 활성화 
         *
         * addons라는 테이블에 애드온의 이름을 등록하는 것으로 활성화를 시키게 된다
         **/
        function doActivate($addon) {
            $oDB = &DB::getInstance();
            $args->addon = $addon;
            return $oDB->executeQuery('addon.insertAddon', $args);
        }

        /**
         * @brief 애드온 비활성화 
         *
         * addons라는 테이블에 애드온의 이름을 제거하는 것으로 비활성화를 시키게 된다
         **/
        function doDeactivate($addon) {
            $oDB = &DB::getInstance();
            $args->addon = $addon;
            return $oDB->executeQuery('addon.deleteAddon', $args);
        }

    }
?>
