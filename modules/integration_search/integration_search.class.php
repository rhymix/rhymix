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
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('integration_search');

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if(count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
				if(is_dir($template_path)) return true;
			}
		}
		
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
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if(count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
				if(is_dir($template_path))
				{
					$config->skin = implode('|@|', $config_parse);
					$oModuleController = getController('module');
					$oModuleController->updateModuleConfig('integration_search', $config);
				}
			}
		}
		
		if (!ModuleModel::getActionForward('IS'))
		{
			$oModuleController = getController('module');
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
