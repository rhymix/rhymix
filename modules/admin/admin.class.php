<?php
    /**
     * @class  admin
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 high class
     **/

    class admin extends ModuleObject {

        /**
         * @brief MVC 에서 공통으로 사용되는 설정등을 모아 놓은것..
         **/
        function init() {

            // 메뉴 아이템 지정
            $menu_item->module->title = Context::getLang('item_module');
            $menu_item->module->act   = 'dispModuleList';
            $menu_item->addon->title  = Context::getLang('item_addon');
            $menu_item->addon->act    = 'dispAddonList';
            $menu_item->plugin->title = Context::getLang('item_plugin');
            $menu_item->plugin->act   = 'dispPluginList';
            $menu_item->layout->title = Context::getLang('item_layout');
            $menu_item->layout->act   = 'dispLayoutList';

            Context::set('menu_item', $menu_item);
        }
    }
?>
