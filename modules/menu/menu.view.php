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
            $oMenuModel = &getModel('menu');
            $menu_list = $oMenuModel->getMenuList();
            Context::set('menu_list', $menu_list);

            $this->setTemplateFile('index');
        }
 
        /**
         * @brief 메뉴 등록/수정 페이지
         **/
        function dispMenuAdminInsert() {
            // 선택된 메뉴의 정보르 구해서 세팅 
            $menu_srl = Context::get('menu_srl');

            // 메뉴의 정보를 가져옴
            $oMenuModel = &getModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);

            // 등록된 메뉴이 없으면 오류 표시
            if(!$menu_info) return $this->dispMenuAdminContent();

            Context::set('menu_info', $menu_info);

            $this->setTemplateFile('menu_insert');
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
