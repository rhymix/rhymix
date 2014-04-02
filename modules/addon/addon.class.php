<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * High class of addon modules
 * @author NAVER (developers@xpressengine.com)
 */
class addon extends ModuleObject
{

	/**
	 * Implement if additional tasks are necessary when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		// Register to add a few
		$oAddonController = getAdminController('addon');
		$oAddonController->doInsert('autolink', 0, 'site', 'Y');
		$oAddonController->doInsert('blogapi');
		$oAddonController->doInsert('member_communication', 0, 'site', 'Y');
		$oAddonController->doInsert('member_extra_info', 0, 'site', 'Y');
		$oAddonController->doInsert('mobile', 0, 'site', 'Y');
		$oAddonController->doInsert('resize_image', 0, 'site', 'Y');
		$oAddonController->doInsert('openid_delegation_id');
		$oAddonController->doInsert('point_level_icon');

		$oAddonController->makeCacheFile(0);
		return new Object();
	}

	/**
	 * A method to check if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists("addons", "is_used_m"))
		{
			return TRUE;
		}
		if(!$oDB->isColumnExists("addons_site", "is_used_m"))
		{
			return TRUE;
		}

		// 2011. 7. 29. add is_fixed column
		if(!$oDB->isColumnExists('addons', 'is_fixed'))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists("addons", "is_used_m"))
		{
			$oDB->addColumn("addons", "is_used_m", "char", 1, "N", TRUE);
		}
		if(!$oDB->isColumnExists("addons_site", "is_used_m"))
		{
			$oDB->addColumn("addons_site", "is_used_m", "char", 1, "N", TRUE);
		}

		// 2011. 7. 29. add is_fixed column
		if(!$oDB->isColumnExists('addons', 'is_fixed'))
		{
			$oDB->addColumn('addons', 'is_fixed', 'char', 1, 'N', TRUE);

			// move addon info to addon_site table
			$output = executeQueryArray('addon.getAddons');
			if($output->data)
			{
				foreach($output->data as $row)
				{
					$args = new stdClass();
					$args->site_srl = 0;
					$args->addon = $row->addon;
					$args->is_used = $row->is_used;
					$args->is_used_m = $row->is_used_m;
					$args->extra_vars = $row->extra_vars;
					executeQuery('addon.insertSiteAddon', $args);
				}
			}
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * Re-generate the cache file
	 *
	 * @return Object
	 */
	function recompileCache()
	{

	}

}
/* End of file addon.class.php */
/* Location: ./modules/addon/addon.class.php */
