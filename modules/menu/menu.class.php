<?php
/**
 * menu class
 * high class of the menu module
 *
 * @author NHN (developers@xpressengine.com)
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

		return new Object();
	}

	/**
	 * A method to check if successfully installed
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		// 2009. 02. 11 menu added to the table site_srl
		if(!$oDB->isColumnExists('menu', 'site_srl')) return true;

		// 2012. 02. 01 title index check
		if(!$oDB->isIndexExists("menu", "idx_title")) return true;

		if(!$oDB->isColumnExists('menu_item', 'is_shortcut'))
		{
			return TRUE;
		}
		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate() {
		$oDB = &DB::getInstance();
		// 2009. 02. 11 menu added to the table site_srl
		if(!$oDB->isColumnExists('menu', 'site_srl'))
		{
			$oDB->addColumn('menu','site_srl','number',11,0,true);
		}

		// 2012. 02. 01 title index check
		if(!$oDB->isIndexExists("menu","idx_title"))
		{
			$oDB->addIndex('menu', 'idx_title', array('title'));
		}

		if(!$oDB->isColumnExists('menu_item', 'is_shortcut'))
		{
			$oDB->addColumn('menu_item', 'is_shortcut', 'char', 1, 'N');

			// check empty url and change shortcut type
			$oMenuAdminModel = &getAdminModel('menu');
			$output = $oMenuAdminModel->getMenus();

			if(is_array($output))
			{
				foreach($output  AS $key=>$value)
				{
					unset($args);
					$args->menu_srl = $value->menu_srl;
					$output2 = executeQueryArray('menu.getMenuItems', $args);
					if(is_array($output2->data))
					{
						foreach($output2->data AS $key2=>$value2)
						{
							if($value2->is_shortcut == 'N' && !$value2->url)
							{
								$value2->is_shortcut = 'Y';

								$output3 = executeQuery('menu.updateMenuItem', $value2);
							}
						}
					}
				}
			}
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * Re-generate the cache file
	 * @return void
	 */
	function recompileCache()
	{
		$oMenuAdminController = &getAdminController('menu');
		// Wanted list of all the blog module
		$output = executeQueryArray("menu.getMenus");
		$list = $output->data;
		if(!count($list)) return;
		// The menu module is used in the re-create all the menu list
		foreach($list as $menu_item)
		{
			$menu_srl = $menu_item->menu_srl;
			$oMenuAdminController->makeXmlFile($menu_srl);
		}
	}
}
/* End of file menu.class.php */
/* Location: ./modules/menu/menu.class.php */
