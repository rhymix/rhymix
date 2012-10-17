<?php
	/**
	 * @class  menuAdminModel
	 * @brief admin model class of the menu module
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/menu
	 * @version 0.1
	 */
    class menuAdminModel extends menu {
		/**
		 * Initialization
		 * @return void
		 */
        function init() {
        }

		/**
		 * Get a list of all menus
		 * @param object $obj
		 * @return object
		 */
        function getMenuList($obj) {
            if(!$obj->site_srl) {
                $site_module_info = Context::get('site_module_info');
                $obj->site_srl = (int)$site_module_info->site_srl;
            }
            $args->site_srl = $obj->site_srl;
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            // document.getDocumentList query execution
            $output = executeQuery('menu.getMenuList', $args);
            // Return if no result or an error occurs
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

		/**
		 * Return all menus
		 * @param int $site_srl
		 * @return array
		 */
        function getMenus($site_srl = null) {
            if(!isset($site_srl)) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = (int)$site_module_info->site_srl;
            }
            // Get information from the DB
            $args->site_srl = $site_srl ;
            $args->menu_srl = $menu_srl;
            $output = executeQueryArray('menu.getMenus', $args);
            if(!$output->data) return;
            $menus = $output->data;
            return $menus;
        }

		/**
		 * Get information of a new menu from the DB
		 * Return DB and XML information of the menu
		 * @param int $menu_srl
		 * @return object
		 */
        function getMenu($menu_srl) {
            // Get information from the DB
            $args->menu_srl = $menu_srl;
            $output = executeQuery('menu.getMenu', $args);
            if(!$output->data) return;

            $menu_info = $output->data;
            $menu_info->xml_file = sprintf('./files/cache/menu/%s.xml.php',$menu_srl);
            $menu_info->php_file = sprintf('./files/cache/menu/%s.php',$menu_srl);
            return $menu_info;
        }

		/**
		 * Get information of a new menu from the DB, search condition is menu title
		 * Return DB and XML information of the menu
		 * @param string $title
		 * @return object
		 */
        function getMenuByTitle($title) {
            // Get information from the DB
            $args->title = $title;
            $output = executeQuery('menu.getMenuByTitle', $args);
            if(!$output->data) return;

			if(is_array($output->data)) $menu_info = $output->data[0];
			else $menu_info = $output->data;

            if($menu_info->menu_srl)
			{
				$menu_info->xml_file = sprintf('./files/cache/menu/%s.xml.php',$menu_info->menu_srl);
	            $menu_info->php_file = sprintf('./files/cache/menu/%s.php',$menu_info->menu_srl);
			}
            return $menu_info;
        }

		/**
		 * Return item information of the menu_srl
		 * group_srls uses a seperator with comma(,) and converts to an array by explode
		 * @param int $menu_item_srl
		 * @return object
		 */
        function getMenuItemInfo($menu_item_srl) {
            // Get the menu information if menu_item_srl exists
            $args->menu_item_srl = $menu_item_srl;
            $output = executeQuery('menu.getMenuItem', $args);
            $node = $output->data;
			settype($node,'object');
            if($node->group_srls) $node->group_srls = explode(',',$node->group_srls);
            else $node->group_srls = array();

            $tmp_name = unserialize($node->name);
            if($tmp_name && count($tmp_name) ) {
                $selected_lang = array();
                $rand_name = $tmp_name[Context::getLangType()];
                if(!$rand_name) $rand_name = array_shift($tmp_name);
                $node->name = $rand_name;
            }
            return $node;
        }

		/**
		 * Return item information of the menu_srl
		 * @return void
		 */
        function getMenuAdminItemInfo()
		{
			$menuItemSrl = Context::get('menu_item_srl');
			$menuItem = $this->getMenuItemInfo($menuItemSrl);

			if(!$menuItem->url)
			{
				$menuItem->moduleType = null;
			}
			else if(!preg_match('/^http/i',$menuItem->url))
			{
				$oModuleModel = &getModel('module');
				$moduleInfo = $oModuleModel->getModuleInfoByMid($menuItem->url, 0);
				if(!$moduleInfo) $menuItem->moduleType = 'url';
				else
				{
					if($moduleInfo->mid == $menuItem->url) {
						$menuItem->moduleType = $moduleInfo->module;
						$menuItem->pageType = $moduleInfo->page_type;
						$menuItem->layoutSrl = $moduleInfo->layout_srl;
					}
				}
			}
			else $menuItem->moduleType = 'url';

			// grant setting
			if(is_array($menuItem->group_srls) && count($menuItem->group_srls) > 0)
			{
				if($menuItem->group_srls[0] == -1)
				{
					$menuItem->grant = 'member';
				}
				else
				{
					$menuItem->grant = 'group';
				}
			}

			// get groups
			$oMemberModel = &getModel('member');
			$oModuleAdminModel = &getAdminModel('module');
			$output = $oMemberModel->getGroups();
			if(is_array($output))
			{
				$groupList = array();
				foreach($output AS $key=>$value)
				{

					$groupList[$value->group_srl]->group_srl = $value->group_srl;
            		if(substr($value->title,0,12)=='$user_lang->') {
						$tmp = $oModuleAdminModel->getLangCode(0, $value->title);
						$groupList[$value->group_srl]->title = $tmp[Context::getLangType()];
					}
					else $groupList[$value->group_srl]->title = $value->title;

					if(in_array($key, $menuItem->group_srls)) $groupList[$value->group_srl]->isChecked = true;
					else $groupList[$value->group_srl]->isChecked = false;
				}
			}
			$menuItem->groupList = $groupList;

			$oModuleController = &getController('module');
			$menuItem->name_key = $menuItem->name;
			$oModuleController->replaceDefinedLangCode($menuItem->name);

			$this->add('menu_item', $menuItem);
        }

		/**
		 * Return menu item list by menu number
		 * @param int $menu_srl
		 * @param int $parent_srl
		 * @param array $columnList
		 * @return object
		 */
		function getMenuItems($menu_srl, $parent_srl = null, $columnList = array())
		{
			$args->menu_srl = $menu_srl;
			$args->parent_srl = $parent_srl;

			$output = executeQueryArray('menu.getMenuItems', $args, $columnList);
			return $output;
		}

		/**
		 * Return menu name in each language to support multi-language
		 * @param string $source_name
		 * @param int $site_srl
		 * @return array
		 */
        function getMenuItemNames($source_name, $site_srl = null) {
            if(!$site_srl) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = (int)$site_module_info->site_srl;
            }
            // Get language code
            $oModuleAdminModel = &getAdminModel('module');
            return $oModuleAdminModel->getLangCode($site_srl, $source_name);
        }

		/**
		 * Get a template by using the menu_srl and retrun.
		 * Return html after compiling tpl on the server in order to add menu information on the admin page
		 * @return void
		 */
        function getMenuAdminTplInfo() {
            // Get information on the menu for the parameter settings
            $menu_item_srl = Context::get('menu_item_srl');
            $parent_srl = Context::get('parent_srl');
            // Get a list of member groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
            // Add a sub-menu if there is parent_srl but not menu_item_srl
            if(!$menu_item_srl && $parent_srl) {
                // Get information of the parent menu
                $parent_info = $this->getMenuItemInfo($parent_srl);
                // Default parameter settings for a new menu
                $item_info->menu_item_srl = getNextSequence();
                $item_info->parent_srl = $parent_srl;
                $item_info->parent_menu_name = $parent_info->name;
            // In case of modifying the existing menu or addting a new menu to the root
            } else {
                // Get information of the menu if menu_item_srl exists
                if($menu_item_srl) $item_info = $this->getMenuItemInfo($menu_item_srl);
                // Get only menu_item_srl if no values found, considering it as adding a new menu
                if(!$item_info->menu_item_srl) {
                    $item_info->menu_item_srl = getNextSequence();
                }
            }
            Context::set('item_info', $item_info);
			//Security
			$security = new Security();
			$security->encodeHTML('group_list..title');
			$security->encodeHTML('item_info.url');
			$security->encodeHTML('item_info.name');

			// Compile the template file into tpl variable and then return it
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'menu_item_info');

            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

		/**
		 * get installed menu type api
		 */
		function getMenuAdminInstalledMenuType()
		{
			$this->add('menu_types', $this->getModuleListInSitemap());
		}

		/**
		 * @brief when menu add in sitemap, select module list
		 * this menu showing with trigger
		 * @param int $site_srl
		 * @return array
		 */
		function getModuleListInSitemap($site_srl = 0)
		{
			$oModuleModel = &getModel('module');
			$columnList = array('module');
			$moduleList = array('page');

			$output = $oModuleModel->getModuleListByInstance($site_srl, $columnList);
			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$value)
				{
					array_push($moduleList, $value->module);
				}
			}

            // after trigger
            $output = ModuleHandler::triggerCall('menu.getModuleListInSitemap', 'after', $moduleList);
            if(!$output->toBool()) return $output;

			$moduleList = array_unique($moduleList);

			$moduleInfoList = array();
			Context::loadLang('modules/page/lang');
			if(is_array($moduleList))
			{
				foreach($moduleList AS $key=>$value)
				{
					if($value == 'page')
					{
						$pageTypeName = Context::getLang('page_type_name');
						$moduleInfo = $oModuleModel->getModuleInfoXml($value);
						$moduleInfo->title = $pageTypeName['ARTICLE'];
						$moduleInfoList['ARTICLE'] = $moduleInfo;
						$wModuleInfo = clone $moduleInfo;
						$wModuleInfo->title = $pageTypeName['WIDGET'];
						$moduleInfoList['WIDGET'] = $wModuleInfo;
						$oModuleInfo = clone $moduleInfo;
						$oModuleInfo->title = $pageTypeName['OUTSIDE'];
						$moduleInfoList['OUTSIDE'] = $oModuleInfo;
					}
					else
					{
						$moduleInfo = $oModuleModel->getModuleInfoXml($value);
						$moduleInfoList[$value] = $moduleInfo;
					}
				}
			}

            return $moduleInfoList;
		}

		public function getMenuAdminSiteMap()
		{
			$menuSrl = Context::get('menu_srl');

			$oModuleModel = &getModel('module');
			$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
			$start_module = $oModuleModel->getSiteInfo(0, $columnList);

			$menuList = array();
			if($menuSrl)
			{
				$output = $this->getMenu($menuSrl);
				$php_file = sprintf('./files/cache/menu/%s.php',$output->menu_srl);
				if(file_exists($php_file)) @include($php_file);

				if(count($menu->list)>0)
				{
					foreach($menu->list AS $key=>$value)
					{
						$this->_menuInfoSetting($menu->list[$key], $start_module);
					}
				}

				$menuItems->menuSrl = $output->menu_srl;
				$menuItems->title = $output->title;
				$menuItems->menuItems = $menu;
				array_push($menuList, $menuItems);
			}
			else
			{
				$menuListFromDB = $this->getMenus();
				if(is_array($menuListFromDB))
				{
					$oAdmin = &getClass('admin');
					foreach($menuListFromDB AS $key=>$value)
					{
						if($value->title == $oAdmin->getAdminMenuName()) unset($output[$key]);
						else
						{
							unset($menu);
							unset($menuItems);
							$value->php_file = sprintf('./files/cache/menu/%s.php',$value->menu_srl);
							if(file_exists($value->php_file)) @include($value->php_file);

							if(count($menu->list)>0)
							{
								foreach($menu->list AS $key2=>$value2)
								{
									$this->_menuInfoSetting($menu->list[$key2], $start_module);
								}
							}

							$menuItems->menuSrl = $value->menu_srl;
							$menuItems->title = $value->title;
							$menuItems->menuItems = $menu;
							array_push($menuList, $menuItems);
						}
					}
				}
			}
			$this->add('menuList', $menuList);
		}

		/**
		 * Setting menu information(recursive)
		 * @param array $menu
		 * @return void
		 */
		private function _menuInfoSetting(&$menu, &$start_module)
		{
			$oModuleModel = &getModel('module');
			if(!empty($menu['url']) && !preg_match('/^http/i', $menu['url']))
			{
				unset($midInfo);
				unset($moduleInfo);
				$midInfo = $oModuleModel->getModuleInfoByMid($menu['url'], 0);
				$moduleInfo = $oModuleModel->getModuleInfoXml($midInfo->module);
				if($moduleInfo->setup_index_act)
				{
					$menu['module_srl'] = $midInfo->module_srl;
					$menu['module'] = $midInfo->module;
					if($midInfo->page_type)
					{
						$menu['module_type'] = $midInfo->page_type;
					}
					else
					{
						$menu['module_type'] = $midInfo->module;
					}
					$menu['setup_index_act'] = $moduleInfo->setup_index_act;
				}
				if($midInfo->mid == $start_module->mid)
				{
					$menu['is_start_module'] = true;
				}
				// setting layout srl for layout management
				$menu['layout_srl'] = $midInfo->layout_srl;
			}
			if(count($menu['list']) > 0)
			{
				foreach($menu['list'] AS $key=>$value)
				{
					$this->_menuInfoSetting($menu['list'][$key], &$start_module);
				}
			}
		}
    }
?>
