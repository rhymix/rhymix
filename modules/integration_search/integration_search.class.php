<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class of the integration_search module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_search extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		// Registered in action forward
		$oModuleController = getController('module');
		$oModuleController->insertActionForward('integration_search', 'view', 'IS');
	}

	/**
	 * Check methoda whether successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		if (!ModuleModel::getActionForward('IS'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		if (!ModuleModel::getActionForward('IS'))
		{
			$oModuleController = ModuleController::getInstance();
			$oModuleController->insertActionForward('integration_search', 'view', 'IS');
		}
	}

	/**
	 * Re-generate the cache file
	 *
	 * @return void
	 */
	function recompileCache()
	{
	}
}
/* End of file integration_search.class.php */
/* Location: ./modules/integration_search/integration_search.class.php */
