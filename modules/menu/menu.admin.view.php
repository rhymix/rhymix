<?php
	/**
	 * menuAdminView class
	 * admin view class of the menu module
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/menu
	 * @version 0.1
	 */
    class menuAdminView extends menu {
		var $tmpMenu = null;

		/**
		 * Initialization
		 * @return void
		 */
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

		/**
		 * The first page of the menu admin
		 * @return void
		 */
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

			//Security
			$security = new Security();
			$security->encodeHTML('menu_list..title');

            $this->setTemplateFile('index');
        }

		/**
		 * Page to insert a menu
		 * @return void
		 */
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
		 * Menu admin page
		 * @return void
		 */
        function dispMenuAdminManagement() {
            // Get information of the menu
            $menu_srl = Context::get('menu_srl');

            if(!$menu_srl) return $this->dispMenuAdminContent();
            // Get information of the menu
            $oMenuModel = &getAdminModel('menu');
            $menu_info = $oMenuModel->getMenu($menu_srl);
            if($menu_info->menu_srl != $menu_srl) return $this->dispMenuAdminContent();

            Context::set('menu_info', $menu_info);

			//Security
			$security = new Security();
			$security->encodeHTML('menu_info..title');

			// Set the layout to be pop-up
            $this->setTemplateFile('menu_management');
        }


		/**
		 * Display a mid list to be able to select on the menu
		 * Perphaps this method not use
		 * @return void
		 */
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
			//Security
			$security = new Security();
			$security->encodeHTML('module_category..title');
			$security->encodeHTML('module_list..module');
			$security->encodeHTML('mid_list..module');
			$security->encodeHTML('mid_list..browser_title');

			// Set a template file
            $this->setTemplateFile('mid_list');
        }

		/**
		 * Site map admin menu index page
		 * @return void
		 */
		function dispMenuAdminSiteMap()
		{
			Context::loadLang(_XE_PATH_.'modules/document/lang/');
			Context::loadLang(_XE_PATH_.'modules/layout/lang/');
            $site_srl = Context::get('site_srl');
			$site_module_info = Context::get('site_module_info');

			if(!$site_srl)
			{
				if($logged_info->is_admin == 'Y' && !$site_keyword) $site_srl = 0;
				else $site_srl = (int)$site_module_info->site_srl;
			}

			$oAdmin = &getClass('admin');

			$oMenuAdminModel = &getAdminModel('menu');
			$menuListFromDB = $oMenuAdminModel->getMenus();
			$output = $menuListFromDB;

			$menuList = array();
			if(is_array($output))
			{
				$menuItems = array();
				foreach($output AS $key=>$value)
				{
					if($value->title == $oAdmin->getAdminMenuName()) unset($output[$key]);
					else
					{
						unset($menu);
						unset($menuItems);
						//$value->xml_file = sprintf('./files/cache/menu/%s.xml.php',$value->menu_srl);
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
			$resultModuleList = $oMenuAdminModel->getModuleListInSitemap($site_srl);
            Context::set('module_list', $resultModuleList);

			$oLayoutModel = &getModel('layout');
			$layoutList = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layoutList);

			// choice theme file
			$theme_file = _XE_PATH_.'files/theme/theme_info.php';
			if(is_readable($theme_file))
			{
				@include($theme_file);
				Context::set('current_layout', $theme_info->layout);
			}
			else
			{
				$oModuleModel = &getModel('module');
				$default_mid = $oModuleModel->getDefaultMid();
				Context::set('current_layout', $default_mid->layout_srl);
			}

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

		/**
		 * Setting menu information(recursive)
		 * @param array $menu
		 * @return void
		 */
		function _menuInfoSetting(&$menu)
		{
			$oModuleModel = &getModel('module');
			if(!preg_match('/^http/i', $menu['url']))
			{
				unset($midInfo);
				unset($moduleInfo);
				$midInfo = $oModuleModel->getModuleInfoByMid($menu['url'], 0);
				$moduleInfo = $oModuleModel->getModuleInfoXml($midInfo->module);
				if($moduleInfo->setup_index_act)
				{
					$menu['module_srl'] = $midInfo->module_srl;
					$menu['setup_index_act'] = $moduleInfo->setup_index_act;
				}
				// setting layout srl for layout management
				$menu['layout_srl'] = $midInfo->layout_srl;
			}
			if(count($menu['list']) > 0)
			{
				foreach($menu['list'] AS $key=>$value)
				{
					$this->_menuInfoSetting($menu['list'][$key]);
				}
			}
		}

		/**
		 * Tree-shaped sorting
		 * @param array $menuItems
		 * @return array
		 */
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
