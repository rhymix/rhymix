<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  menuAdminModel
 * @brief admin model class of the menu module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/menu
 * @version 0.1
 */
class menuAdminModel extends menu
{
	private $menuSrlWithinHome = 0;

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Get a list of all menus
	 * @param object $obj
	 * @return object
	 */
	function getMenuList($obj)
	{
		$args = new stdClass;
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
	 * @return array
	 */
	function getMenus()
	{
		// Get information from the DB
		$args = new stdClass();
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
	function getMenu($menu_srl)
	{
		// Get information from the DB
		$args = new stdClass();
		$args->menu_srl = $menu_srl;
		$output = executeQuery('menu.getMenu', $args);
		if(!$output->data) return;

		$menu_info = $output->data;
		$menu_info->xml_file = sprintf('./files/cache/menu/%d.xml.php',$menu_srl);
		$menu_info->php_file = sprintf('./files/cache/menu/%d.php',$menu_srl);
		return $menu_info;
	}
	
	/**
	 * Get actual menu info data
	 * 
	 * @param int $menu_srl
	 * @return object
	 */
	public static function getMenuInfo(int $menu_srl): \stdClass
	{
		$menu = new stdClass;
		$menu->list = [];
		
		$filename = sprintf('./files/cache/menu/%d.php', $menu_srl);
		if (!FileHandler::exists($filename))
		{
			getAdminController('menu')->makeXmlFile($menu_srl);
		}
		if (FileHandler::exists($filename))
		{
			include $filename;
		}
		
		return $menu;
	}

	/**
	 * Get information of a new menu from the DB, search condition is menu title
	 * Return DB and XML information of the menu
	 * @param string $title
	 * @return object
	 */
	function getMenuByTitle($title)
	{
		// Get information from the DB
		if(!is_array($title))
		{
			$title = array($title);
		}
		$args = new stdClass();
		$args->title = $title;
		$output = executeQuery('menu.getMenuByTitle', $args);
		if(!$output->data) return;

		if(is_array($output->data)) $menu_info = $output->data[0];
		else $menu_info = $output->data;

		if($menu_info->menu_srl)
		{
			$menu_info->xml_file = sprintf('./files/cache/menu/%d.xml.php',$menu_info->menu_srl);
			$menu_info->php_file = sprintf('./files/cache/menu/%d.php',$menu_info->menu_srl);
		}
		return $menu_info;
	}

	/**
	 * Get information of a new menu from the DB, search condition is menu title
	 * Return DB and XML information of the menu(list Type)
	 * @param string $title
	 * @return object
	 */
	function getMenuListByTitle($title)
	{
		// Get information from the DB
		$args = new stdClass();
		$args->title = $title;
		$output = executeQueryArray('menu.getMenuByTitle', $args);
		if(!$output->data)
		{
			return array();
		}

		return $output->data;
	}

	/**
	 * Return item information of the menu_srl
	 * group_srls uses a seperator with comma(,) and converts to an array by explode
	 * @param int $menu_item_srl
	 * @return object
	 */
	function getMenuItemInfo($menu_item_srl)
	{
		// Get the menu information if menu_item_srl exists
		$args = new stdClass();
		$args->menu_item_srl = $menu_item_srl;
		$output = executeQuery('menu.getMenuItem', $args);
		$node = $output->data;
		settype($node,'object');
		if($node->group_srls) $node->group_srls = explode(',',$node->group_srls);
		else $node->group_srls = array();

		$tmp_name = unserialize($node->name);
		if($tmp_name && count($tmp_name))
		{
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
		else if(strncasecmp('http', $menuItem->url, 4) !== 0)
		{
			$oModuleModel = getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByMid($menuItem->url, 0);
			if(!$moduleInfo) $menuItem->moduleType = 'url';
			else
			{
				if($moduleInfo->mid == $menuItem->url)
				{
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
			else if($menuItem->group_srls[0] == -3)
			{
				$menuItem->grant = 'manager';
			}
			else
			{
				$menuItem->grant = 'group';
			}
		}

		// get groups
		$oMemberModel = getModel('member');
		$oModuleAdminModel = getAdminModel('module');
		$output = $oMemberModel->getGroups();
		if(is_array($output))
		{
			$groupList = array();
			foreach($output AS $key=>$value)
			{
				$groupList[$value->group_srl] = new stdClass();
				$groupList[$value->group_srl]->group_srl = $value->group_srl;
				if(substr($value->title,0,12)=='$user_lang->')
				{
					$tmp = $oModuleAdminModel->getLangCode(0, $value->title);
					$groupList[$value->group_srl]->title = $tmp[Context::getLangType()];
				}
				else $groupList[$value->group_srl]->title = $value->title;

				if(in_array($key, $menuItem->group_srls)) $groupList[$value->group_srl]->isChecked = true;
				else $groupList[$value->group_srl]->isChecked = false;
			}
		}
		$menuItem->groupList = $groupList;
		$menuItem->name_key = $menuItem->name;
		$menuItem->name = Context::replaceUserLang($menuItem->name);

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
		$args = new stdClass();
		$args->menu_srl = $menu_srl;
		$args->parent_srl = $parent_srl;

		$output = executeQueryArray('menu.getMenuItems', $args, $columnList);
		return $output;
	}

	/**
	 * Return menu name in each language to support multi-language
	 * @param string $source_name
	 * @return array
	 */
	function getMenuItemNames($source_name)
	{
		// Get language code
		$oModuleAdminModel = getAdminModel('module');
		return $oModuleAdminModel->getLangCode(0, $source_name, TRUE);
	}

	/**
	 * Get a template by using the menu_srl and retrun.
	 * Return html after compiling tpl on the server in order to add menu information on the admin page
	 * @return void
	 */
	function getMenuAdminTplInfo()
	{
		// Get information on the menu for the parameter settings
		$menu_item_srl = Context::get('menu_item_srl');
		$parent_srl = Context::get('parent_srl');
		// Get a list of member groups
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		// Add a sub-menu if there is parent_srl but not menu_item_srl
		$item_info = new stdClass;
		if(!$menu_item_srl && $parent_srl)
		{
			// Get information of the parent menu
			$parent_info = $this->getMenuItemInfo($parent_srl);
			// Default parameter settings for a new menu
			$item_info->menu_item_srl = getNextSequence();
			$item_info->parent_srl = $parent_srl;
			$item_info->parent_menu_name = $parent_info->name;
			// In case of modifying the existing menu or addting a new menu to the root
		}
		else
		{
			// Get information of the menu if menu_item_srl exists
			if($menu_item_srl) $item_info = $this->getMenuItemInfo($menu_item_srl);
			// Get only menu_item_srl if no values found, considering it as adding a new menu
			if(!$item_info->menu_item_srl)
			{
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
		$oModuleModel = getModel('module');
		$oAutoinstallModel = getModel('autoinstall');
		$this->add('menu_types', $this->getModuleListInSitemap(0));

		$_allModules = FileHandler::readDir('./modules', '/^([a-zA-Z0-9_-]+)$/');
		sort($_allModules);

		$allModules = array();

		Context::loadLang('modules/page/lang');

		$oAutoinstallAdminModel = getAdminModel('autoinstall');
		$config = $oAutoinstallAdminModel->getAutoInstallAdminModuleConfig();
		
		foreach($_allModules as $module_name)
		{
			$module = $oModuleModel->getModuleInfoXml($module_name);
			if(!isset($module)) continue;
			$defaultSkin = $oModuleModel->getModuleDefaultSkin($module_name, 'P');
			$defaultMobileSkin = $oModuleModel->getModuleDefaultSkin($module_name, 'M');
			$skinInfo = $oModuleModel->loadSkinInfo(ModuleHandler::getModulePath($module_name), $defaultSkin);
			$mobileSkinInfo = $oModuleModel->loadSkinInfo(ModuleHandler::getModulePath($module_name), $defaultMobileSkin, 'm.skins');
			if($defaultMobileSkin === '/USE_RESPONSIVE/' && !$mobileSkinInfo || !$mobileSkinInfo->title)
			{
				$mobileSkinInfo = $mobileSkinInfo ?: new stdClass;
				$mobileSkinInfo->title = lang('use_responsive_pc_skin');
			}
			$module->defaultSkin = new stdClass();
			$module->defaultSkin->skin = $defaultSkin;
			$module->defaultSkin->title = $skinInfo->title ? $skinInfo->title : $defaultSkin;
			$module->defaultMobileSkin = new stdClass();
			$module->defaultMobileSkin->skin = $defaultMobileSkin;
			$module->defaultMobileSkin->title = $mobileSkinInfo->title ? $mobileSkinInfo->title : $defaultMobileSkin;
			$module->package_srl = $oAutoinstallModel->getPackageSrlByPath('./modules/' . $module_name);
			$module->url = $config->location_site . '?mid=download&package_srl=' . $module->package_srl;

			if($module_name == 'page')
			{
				$pageTypeName = lang('page_type_name');
				$module->title = $pageTypeName['ARTICLE'];
				$allModules['ARTICLE'] = $module;
				$wModuleInfo = clone $module;
				unset($wModuleInfo->default_skin, $wModuleInfo->default_mskin);
				$wModuleInfo->title = $pageTypeName['WIDGET'];
				$wModuleInfo->no_skin = 'Y';
				$allModules['WIDGET'] = $wModuleInfo;
				$oModuleInfo = clone $module;
				unset($oModuleInfo->default_skin, $oModuleInfo->default_mskin);
				$oModuleInfo->title = $pageTypeName['OUTSIDE'];
				$oModuleInfo->no_skin = 'Y';
				$allModules['OUTSIDE'] = $oModuleInfo;
			}
			else
			{
				$allModules[$module_name] = $module;
			}
		}

		$this->add('all_modules', $allModules);
	}

	/**
	 * @brief when menu add in sitemap, select module list
	 * this menu showing with trigger
	 * @return array
	 */
	function getModuleListInSitemap()
	{
		$oModuleModel = getModel('module');
		$moduleList = array('page');

		$output = $oModuleModel->getModuleListByInstance();
		if(is_array($output->data))
		{
			foreach($output->data as $value)
			{
				if($value->instanceCount >= 1)
				{
					$moduleList[] = $value->module;
				}
			}
		}

		// after trigger
		ModuleHandler::triggerCall('menu.getModuleListInSitemap', 'after', $moduleList);

		$localModuleList = array_unique($moduleList);

		$oAutoinstallModel = getModel('autoinstall');

		// get have instance
		$remotePackageList = $oAutoinstallModel->getHaveInstance(array('path'));
		$remoteModuleList = array();
		foreach($remotePackageList as $package)
		{
			if(strpos($package->path, './modules/') !== 0) continue;

			$pathInfo = explode('/', $package->path);
			$remoteModuleList[] = $pathInfo[2];
		}

		// all module list
		$allModuleList = FileHandler::readDir('./modules', '/^([a-zA-Z0-9_-]+)$/');

		// union have instance and all module list
		$haveInstance = array_intersect($remoteModuleList, $allModuleList);
		$haveDirectory = array_intersect($localModuleList, $allModuleList);

		// union
		$moduleList = array_unique(array_merge($haveDirectory, $haveInstance));

		$moduleInfoList = array();
		Context::loadLang('modules/page/lang');
		if(is_array($moduleList))
		{
			foreach($moduleList as $value)
			{
				$moduleInfo = $oModuleModel->getModuleInfoXml($value);

				if($value == 'page')
				{
					$pageTypeName = lang('page_type_name');
					$moduleInfo->title = $pageTypeName['ARTICLE'];
					$moduleInfoList['ARTICLE'] = $moduleInfo;
					$wModuleInfo = clone $moduleInfo;
					unset($wModuleInfo->default_skin, $wModuleInfo->default_mskin);
					$wModuleInfo->title = $pageTypeName['WIDGET'];
					$wModuleInfo->no_skin = 'Y';
					$moduleInfoList['WIDGET'] = $wModuleInfo;
					$oModuleInfo = clone $moduleInfo;
					unset($oModuleInfo->default_skin, $oModuleInfo->default_mskin);
					$oModuleInfo->title = $pageTypeName['OUTSIDE'];
					$oModuleInfo->no_skin = 'Y';
					$moduleInfoList['OUTSIDE'] = $oModuleInfo;
				}
				else
				{
					$moduleInfoList[$value] = $moduleInfo;
				}
			}
		}

		return $moduleInfoList;
	}

	public function getMenuAdminSiteMap()
	{
		$menuSrl = intval(Context::get('menu_srl'));

		$oModuleModel = getModel('module');
		$oMenuAdminController = getAdminController('menu');
		$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
		
		$start_module_list = executeQuery('module.getDomainInfo', new stdClass);
		$start_module = $start_module_list->data;

		$menuList = array();
		if($menuSrl)
		{
			$isMenuFixed = false;
			$output = $this->getMenu($menuSrl);
			$php_file = sprintf(RX_BASEDIR . 'files/cache/menu/%d.php',$output->menu_srl);
			if(file_exists($php_file))
			{
				include($php_file);
			}
			else 
			{
				$oMenuAdminController->makeXmlFile($menuSrl);
			}
			if(!$menu)
			{
				$menu = new stdClass;
				$menu->list = array();
			}

			if(count($menu->list)>0)
			{
				foreach($menu->list AS $key=>$value)
				{
					$this->_menuInfoSetting($menu->list[$key], $start_module, $isMenuFixed, $menuSrl);
				}
				$menu->list = array_values($menu->list);
			}
			else
			{
				$menu->list = array();
			}

			// menu recreate
			if($isMenuFixed)
			{
				$oMenuAdminController->makeXmlFile($menuSrl);
			}

			$menuItems->menuSrl = $output->menu_srl;
			$menuItems->title = $output->title;
			$menuItems->menuItems = $menu;
			$menuList[] = $menuItems;
		}
		else
		{
			$menuListFromDB = $this->getMenus();
			if(is_array($menuListFromDB))
			{
				$oAdmin = getClass('admin');
				foreach($menuListFromDB AS $key=>$value)
				{
					if($value->title == $oAdmin->getAdminMenuName()) unset($output[$key]);
					else
					{
						unset($menu);
						unset($menuItems);
						$value->php_file = sprintf(RX_BASEDIR . 'files/cache/menu/%d.php',$value->menu_srl);
						if(!file_exists($value->php_file))
						{
							$oMenuAdminController->makeXmlFile($value->menu_srl);
						}

						include($value->php_file);
						if(!$menu)
						{
							$menu = new stdClass;
							$menu->list = array();
						}

						$isMenuFixed = false;
						if(count($menu->list) > 0)
						{
							foreach($menu->list AS $key2=>$value2)
							{
								$this->_menuInfoSetting($menu->list[$key2], $start_module, $isMenuFixed, $value->menu_srl);
							}
							$menu->list = array_values($menu->list);
						}
						else
						{
							$menu->list = array();
						}

						// menu recreate
						if($isMenuFixed)
						{
							$oMenuAdminController->makeXmlFile($value->menu_srl);
						}

						$menuItems = new stdClass();
						$menuItems->menuSrl = $value->menu_srl;
						$menuItems->title = $value->title;
						$menuItems->menuItems = $menu;

						// If include home menu, move first
						if($value->menu_srl == $this->menuSrlWithinHome)
						{
							$menuList[-1] = $menuItems;
						}
						else
						{
							$menuList[] = $menuItems;
						}
					}
				}
			}
		}
		ksort($menuList);
		$menuList = array_values($menuList);
		$this->add('menuList', $menuList);
	}

	/**
	 * Get module's detail setup contents
	 * @return void
	 */
	public function getMenuAdminDetailSetup()
	{
		$menuItemSrl = Context::get('menu_item_srl');
		if(!$menuItemSrl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$menuItemInfo = $this->getMenuItemInfo($menuItemSrl);

		// if menu is shortcut
		if($menuItemInfo->is_shortcut == 'Y')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// get module info
		$oModuleModel = getModel('module');
		$moduleInfo = $oModuleModel->getModuleInfoByMid($menuItemInfo->url);

		// get xml info
		$moduleConfInfo = $oModuleModel->getModuleInfoXml($moduleInfo->module);

		if($moduleConfInfo->setup_index_act)
		{
			$setupUrl = getNotEncodedUrl('', 'module', 'admin', 'act', $moduleConfInfo->setup_index_act, 'module_srl', $moduleInfo->module_srl, 'isLayoutDrop', '1');
		}

		if($moduleConfInfo->simple_setup_index_act)
		{
			$oTargetmoduleAdminModel = getAdminModel($moduleInfo->module);
			$advancedSetupUrl = getUrl('', 'module', 'admin', 'act', $moduleConfInfo->setup_index_act, 'module_srl', $moduleInfo->module_srl);
			$simpleSetupHtml = $oTargetmoduleAdminModel->{$moduleConfInfo->simple_setup_index_act}($moduleInfo->module_srl, $advancedSetupUrl);

			if($simpleSetupHtml)
			{
				$this->add('simpleSetupHtml', $simpleSetupHtml);
			}
		}
		$this->add('setupUrl', $setupUrl);
	}

	/**
	 * Setting menu information(recursive)
	 * @param array $menu
	 * @return void
	 */
	private function _menuInfoSetting(&$menu, &$start_module, &$isMenuFixed, $menuSrl)
	{
		$oModuleModel = getModel('module');
		if(!is_array($start_module))
		{
			$start_module = $start_module ? array($start_module) : array();
		}
		
		// if url is empty and is_shortcut is 'N', change to is_shortcut 'Y'
		if(!$menu['url'] && $menu['is_shortcut'] == 'N')
		{
			$menu['is_shortcut'] = 'Y';

			$args = new stdClass;
			$args->menu_item_srl = $menu['node_srl'];
			$args->is_shortcut = 'Y';
			if($menu['menu_name_key']) $args->name = $menu['menu_name_key'];
			else $args->name = $menu['menu_name'];
			$output = executeQuery('menu.updateMenuItem', $args);

			$isMenuFixed = true;
		}

		//if menu type is module menu
		//if(!empty($menu['url']) && !preg_match('/^http/i', $menu['url']))
		if($menu['is_shortcut'] != 'Y')
		{
			unset($midInfo, $moduleInfo);
			$midInfo = $oModuleModel->getModuleInfoByMid($menu['url']);
			$moduleInfo = $oModuleModel->getModuleInfoXml($midInfo->module);

			if($midInfo)
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
			}

			if($moduleInfo->setup_index_act)
			{
				$menu['setup_index_act'] = $moduleInfo->setup_index_act;
			}
			else if($moduleInfo->default_index_act)
			{
				$menu['setup_index_act'] = $moduleInfo->default_index_act;
			}

			if($menu['is_shortcut'] == 'N')
			{
				foreach($start_module as $start_module_info)
				{
					if($midInfo->mid == $start_module_info->mid)
					{
						$menu['is_start_module'] = true;
						$menu['is_start_module_of'] = $start_module_info->domain;
						if($start_module_info->is_default_domain === 'Y')
						{
							$this->menuSrlWithinHome = $menuSrl;
						}
						break;
					}
				}
			}

			// setting layout srl for layout management
			$menu['layout_srl'] = $midInfo->layout_srl;
			$menu['browser_title'] = $midInfo->browser_title;
		}
		if(count($menu['list']) > 0)
		{
			foreach($menu['list'] as $key=>$value)
			{
				$this->_menuInfoSetting($menu['list'][$key], $start_module, $isMenuFixed, $menuSrl);
			}
			$menu['list'] = array_values($menu['list']);
		}
		else
		{
			$menu['list'] = array();
		}
	}
}
/* End of file menu.admin.model.php */
/* Location: ./modules/menu/menu.admin.model.php */
