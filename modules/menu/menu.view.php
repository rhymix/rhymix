<?php
    /**
     * @class  menuView
     * @author zero (zero@nzeo.com)
     * @brief  menu 모듈의 View class
     **/

    class menuView extends menu {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 메뉴 관리의 첫 페이지
         **/
        function dispMenuAdminContent() {
            // 등록된 메뉴 목록을 구해옴 
            $obj->page = Context::get('page');
            $obj->sort_index = 'listorder';
            $obj->list_count = 20;
            $obj->page_count = 20;

            $oMenuModel = &getModel('menu');
            $output = $oMenuModel->getMenuList($obj);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('menu_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }
 
        /**
         * @brief 메뉴 등록 페이지
         **/
        function dispMenuAdminInsert() {
            // 선택된 메뉴의 정보르 구해서 세팅 
            $menu_srl = Context::get('menu_srl');

            if($menu_srl) {
                // 메뉴의 정보를 가져옴
                $oMenuModel = &getModel('menu');
                $menu_info = $oMenuModel->getMenu($menu_srl);
                if($menu_info->menu_srl == $menu_srl) Context::set('menu_info', $menu_info);
            }

            $this->setTemplateFile('menu_insert');
        }
 
        /**
         * @brief 메뉴 관리 페이지
         **/
        function dispMenuAdminManagement() {
            // 선택된 메뉴의 정보르 구해서 세팅 
            $menu_srl = Context::get('menu_srl');

            if(!$menu_srl) return $this->dispMenuAdminContent();

            // 메뉴의 정보를 가져옴
            $oMenuModel = &getModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);
            if($menu_info->menu_srl == $menu_srl) Context::set('menu_info', $menu_info);
            else return $this->dispMenuAdminContent();

            Context::set('menu_info', $menu_info);

            $this->setTemplateFile('menu_management');
        }


        /**
         * @brief 메뉴에서 선택할 수 있는 mid목록을 보여줌
         **/
        function dispMenuAdminMidList() {
            // mid 목록을 구해옴
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

            // 메뉴을 팝업으로 지정
            $this->setMenuFile('popup_menu');

            // 템플릿 파일 지정
            $this->setTemplateFile('mid_list');
        }

        /**
         * @brief 메뉴 목록을 보여줌
         **/
        function dispMenuAdminDownloadedList() {
            // 메뉴 목록을 세팅
            $oMenuModel = &getModel('menu');
            $menu_list = $oMenuModel->getDownloadedMenuList();
            Context::set('menu_list', $menu_list);

            $this->setTemplateFile('downloaded_menu_list');
        }
    }
?>
