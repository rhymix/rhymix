<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * menuAdminView class
 * admin view class of the menu module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/menu
 * @version 0.1
 */
class menuAdminView extends menu
{
	var $tmpMenu = null;

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
	}

	
	/**
	 * Site map admin menu index page
	 * @return void
	 */
	function dispMenuAdminSiteMap()
	{
		Context::loadLang(_XE_PATH_.'modules/document/lang/');
		Context::loadLang(_XE_PATH_.'modules/layout/lang/');
		Context::loadLang(_XE_PATH_.'modules/autoinstall/lang/');
		$site_srl = Context::get('site_srl');
		$site_module_info = Context::get('site_module_info');

		if(!$site_srl)
		{
			if($logged_info->is_admin == 'Y' && !$site_keyword) $site_srl = 0;
			else $site_srl = (int)$site_module_info->site_srl;
		}

		// process for unlinked modules
		if($site_srl == 0)
		{
			$oMenuController = getAdminController('menu');
			$oMenuController->linkAllModuleInstancesToSitemap();
		}

		$oAdmin = getClass('admin');

		$oMenuAdminModel = getAdminModel('menu');
		$menuListFromDB = $oMenuAdminModel->getMenus();
		if(is_array($menuListFromDB)) $output = array_reverse($menuListFromDB);

		$menuList = array();
		if(is_array($output))
		{
			$menuItems = array();
			foreach($output as $key=>$value)
			{
				if($value->title == $oAdmin->getAdminMenuName()) unset($output[$key]);
				else
				{
					unset($menu, $menuItems);
					//$value->xml_file = sprintf('./files/cache/menu/%s.xml.php',$value->menu_srl);
					$value->php_file = sprintf(_XE_PATH_ . 'files/cache/menu/%s.php',$value->menu_srl);
					if(file_exists($value->php_file)) include($value->php_file);

					if(count($menu->list)>0)
					{
						foreach($menu->list as $key2=>$value2)
						{
							$this->_menuInfoSetting($menu->list[$key2]);
						}
					}

					//array_push($menuList, $value->xml_file);
					$menuItems = new stdClass();
					$menuItems->menuSrl = $value->menu_srl;
					$menuItems->title = $value->title;
					$menuItems->desc = $value->desc;
					$menuItems->menuItems = $menu;
					$menuList[] = $menuItems;
				}
			}
		}
		Context::set('menu_list', $menuList);

		// get installed module list
		$oPageController = getController('page'); //for lang
		$resultModuleList = $oMenuAdminModel->getModuleListInSitemap($site_srl);
		Context::set('module_list', $resultModuleList);

		$oLayoutModel = getModel('layout');
		$layoutList = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layoutList);

		// choice theme file
		$theme_file = _XE_PATH_.'files/theme/theme_info.php';
		if(is_readable($theme_file))
		{
			include($theme_file);
			Context::set('current_layout', $theme_info->layout);
		}
		else
		{
			$oModuleModel = getModel('module');
			$default_mid = $oModuleModel->getDefaultMid();
			Context::set('current_layout', $default_mid->layout_srl);
		}

		// get default group list
		$oMemberModel = getModel('member');
		$output = $oMemberModel->getGroups();
		if(is_array($output))
		{
			$groupList = array();
			foreach($output as $value)
			{
				$groupList[$value->group_srl] = new stdClass();
				$groupList[$value->group_srl]->group_srl = $value->group_srl;
				$groupList[$value->group_srl]->title = $value->title;
			}
		}
		Context::set('group_list', $groupList);

		$this->setTemplateFile('sitemap');
	}

	/**
	 * Site design admin page
	 * @return void
	 */
	public function dispMenuAdminSiteDesign()
	{
		$this->setTemplateFile('sitemap');
	}

	/**
	 * Setting menu information(recursive)
	 * @param array $menu
	 * @return void
	 */
	function _menuInfoSetting(&$menu)
	{
		$oModuleModel = getModel('module');
		if($menu['url'] && strncasecmp('http', $menu['url'], 4) !== 0)
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
			foreach($menu['list'] as $key=>$value)
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
			foreach($menuItems as $value)
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
/* End of file menu.admin.view.php */
/* Location: ./modules/menu/menu.admin.view.php */