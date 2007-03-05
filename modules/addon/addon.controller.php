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
         * @brief 애드온 활성화 
         *
         * addons라는 테이블에 애드온의 이름을 등록하는 것으로 활성화를 시키게 된다
         **/
        function procActivate() {
            $addon = Context::get('addon');
            if(!$addon) return;

            $oDB = &DB::getInstance();
            $args->addon = $addon;
            $output = $oDB->executeQuery('addon.insertAddon', $args);
            $this->setRedirectUrl("./?module=admin&act=dispAddonList");
        }

        /**
         * @brief 애드온 비활성화 
         *
         * addons라는 테이블에 애드온의 이름을 제거하는 것으로 비활성화를 시키게 된다
         **/
        function procDeactivate() {
            $addon = Context::get('addon');
            if(!$addon) return;

            $oDB = &DB::getInstance();
            $args->addon = $addon;
            $output = $oDB->executeQuery('addon.deleteAddon', $args);
            $this->setRedirectUrl("./?module=admin&act=dispAddonList");
        }

    }
?>
