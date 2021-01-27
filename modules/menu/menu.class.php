<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * menu class
 * high class of the menu module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/menu
 * @version 0.1
 */
class menu extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		// Create a directory to use menu
		FileHandler::makeDir('./files/cache/menu');
	}

	/**
	 * A method to check if successfully installed
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		
		// 2015. 06. 15 add column desc
		if(!$oDB->isColumnExists('menu_item', 'desc'))
		{
			return true;
		}
		
		// 2021. 01. 20 add column icon
		if(!$oDB->isColumnExists('menu_item', 'icon'))
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		
		// 2015. 06. 15 add column desc
		if(!$oDB->isColumnExists('menu_item', 'desc'))
		{
			$oDB->addColumn('menu_item', 'desc', 'varchar', 250, null, false, 'name');
		}
		
		// 2021. 01. 20 add column icon
		if(!$oDB->isColumnExists('menu_item', 'icon'))
		{
			$oDB->addColumn('menu_item', 'icon', 'varchar', 250, null, false, 'name');
		}
	}

	/**
	 * Re-generate the cache file
	 * @return void
	 */
	function recompileCache()
	{
		$oMenuAdminController = getAdminController('menu');
		$oMenuAdminModel = getAdminModel('menu');

		// get home module id
		$oModuleModel = getModel('module');
		$columnList = array('modules.mid',);
		$output = $oModuleModel->getSiteInfo(0, $columnList);
		$homeModuleMid = $output->mid;
		$homeMenuSrl = NULL;

		// Wanted list of all the blog module
		$output = executeQueryArray("menu.getMenus");
		$list = $output->data;
		if(!count($list)) return;
		// The menu module is used in the re-create all the menu list
		foreach($list as $menu_item)
		{
			$menu_srl = $menu_item->menu_srl;
			$oMenuAdminController->makeXmlFile($menu_srl);

			// for homeSitemap.php regenrate
			if(!$homeMenuSrl)
			{
				$menuItemList = $oMenuAdminModel->getMenuItems($menu_srl);

				if(is_array($menuItemList->data))
				{
					foreach($menuItemList->data AS $key=>$value)
					{
						if($homeModuleMid == $value->url)
						{
							$homeMenuSrl = $menu_srl;
							break;
						}
					}
				}
			}
		}

		if($homeMenuSrl)
		{
			$oMenuAdminController->makeHomemenuCacheFile($homeMenuSrl);
		}
	}
}
/* End of file menu.class.php */
/* Location: ./modules/menu/menu.class.php */