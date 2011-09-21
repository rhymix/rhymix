<?php
    /**
     * @class  menuAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the menu module
     **/

    class menuAdminView extends menu {
		var $tmpMenu = null;

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

        /**
         * @brief Site map admin menu index page
         **/
		function dispMenuAdminSiteMap()
		{
			$oMenuAdminModel = &getAdminModel('menu');
			$output = array_reverse($oMenuAdminModel->getMenus());

			$menuList = array();
			if(is_array($output))
			{
				$menuItems = array();
				foreach($output AS $key=>$value)
				{
					if($value->title == '__XE_ADMIN__') unset($output[$key]);
					else
					{
						unset($menu);
						unset($menuItems);
						$value->xml_file = sprintf('./files/cache/menu/%s.xml.php',$value->menu_srl);
						$value->php_file = sprintf('./files/cache/menu/%s.php',$value->menu_srl);
						if(file_exists($value->php_file)) @include($value->php_file);

						if(count($menu->list)>0)
						{
							foreach($menu->list AS $key2=>$value2)
							{
								$this->_menuInfoSetting($menu->list[$key2]);
							}
						}

						//array_push($menuList, $value->xml_file);
						$menuItems->menuSrl = $value->menu_srl;
						$menuItems->title = $value->title;
						$menuItems->menuItems = $menu;
						array_push($menuList, $menuItems);
					}
				}
			}
            Context::set('menu_list', $menuList);

			// get installed module list
			$oPageController = &getController('page');	//for lang
			$resultModuleList = $oMenuAdminModel->getModuleListInSitemap();
            Context::set('module_list', $resultModuleList);

			// get default group list
			$oMemberModel = &getModel('member');
			$output = $oMemberModel->getGroups();
			if(is_array($output))
			{
				$groupList = array();
				foreach($output AS $key=>$value)
				{
					$groupList[$value->group_srl]->group_srl = $value->group_srl;
					$groupList[$value->group_srl]->title = $value->title;
				}
			}
            Context::set('group_list', $groupList);

            $this->setTemplateFile('sitemap');
		}

		function _menuInfoSetting(&$menu)
		{
			$oModuleModel = &getModel('module');
			if(!preg_match('/^http/i', $menu['url']))
			{
				unset($midInfo);
				unset($moduleInfo);
				$midInfo = $oModuleModel->getModuleInfoByMid($menu['url']);
				$moduleInfo = $oModuleModel->getModuleInfoXml($midInfo->module);
				if($moduleInfo->setup_index_act)
				{
					$menu['module_srl'] = $midInfo->module_srl;
					$menu['setup_index_act'] = $moduleInfo->setup_index_act;
				}
			}
			if(count($menu['list']) > 0)
			{
				foreach($menu['list'] AS $key=>$value)
				{
					$this->_menuInfoSetting($menu['list'][$key]);
				}
			}
		}

		function _arrangeMenuItem($menuItems)
		{
			if(is_array($menuItems))
			{
				$arrangedMenuItemList = array();
				foreach($menuItems AS $key=>$value)
				{
					if($value->parent_srl == 0)
					{
						$arrangedMenuItemList[$value->menu_item_srl] = array('name'=>$value->name, 'subMenu'=>array());
					}

					if($value->parent_srl > 0 && isset($arrangedMenuItemList[$value->parent_srl]))
					{
						$arrangedMenuItemList[$value->parent_srl]['subMenu'][$value->menu_item_srl] = $value;
					}
				}
			}
			return $arrangedMenuItemList;
		}
    }
?>
