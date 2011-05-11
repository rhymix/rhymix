<?php
    /**
     * @class  menuAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the menu module
     **/

    class menuAdminView extends menu {

        /**
         * @brief Initialization
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief The first page of the menu admin
         **/
        function dispMenuAdminContent() {
            // Get a list of registered menus
            $obj->page = Context::get('page');
            $obj->sort_index = 'listorder';
            $obj->list_count = 20;
            $obj->page_count = 20;

            $oMenuModel = &getAdminModel('menu');
            $output = $oMenuModel->getMenuList($obj);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('menu_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }
 
        /**
         * @brief Page to insert a menu
         **/
        function dispMenuAdminInsert() {
            // Set the menu with menu information            
            $menu_srl = Context::get('menu_srl');

            if($menu_srl) {
                // Get information of the menu
                $oMenuModel = &getAdminModel('menu');
                $menu_info = $oMenuModel->getMenu($menu_srl);
                if($menu_info->menu_srl == $menu_srl) Context::set('menu_info', $menu_info);
            }

            $this->setTemplateFile('menu_insert');
        }
 
        /**
         * @brief Menu admin page
         **/
        function dispMenuAdminManagement() {
            // Get information of the menu
            $menu_srl = Context::get('menu_srl');

            if(!$menu_srl) return $this->dispMenuAdminContent();
            // Get information of the menu
            $oMenuModel = &getAdminModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);
            if($menu_info->menu_srl != $menu_srl) return $this->dispMenuAdminContent();

            Context::set('menu_info', $menu_info);
            // Set the layout to be pop-up
            $this->setTemplateFile('menu_management');
        }


        /**
         * @brief Display a mid list to be able to select on the menu
		 * @perphaps this method not use
         **/
        function dispMenuAdminMidList() {
            $oModuleModel = &getModel('module');
            // Get a list of module categories
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);
            // Get a list of modules
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);
            // Get a list of mid
            $args->module_category_srl = Context::get('module_category_srl');
            $args->module = Context::get('target_module');
			$columnList = array('module_srl', 'module', 'module_category_srl', 'browser_title');
            $mid_list = $oModuleModel->getMidList($args, $columnList);
            Context::set('mid_list', $mid_list);
            // Set the menu as a pop-up
            $this->setLayoutFile('popup_layout');
            // Set a template file
            $this->setTemplateFile('mid_list');
        }
    }
?>
