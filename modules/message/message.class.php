<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  message
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of message module
 */
class message extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/message/', $config_parse[0]);
				if(is_dir($template_path)) return true;
			}
		}
		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/message/', $config_parse[0]);
				if(is_dir($template_path))
				{
					$config->skin = implode('|@|', $config_parse);
					$oModuleController = getController('module');
					$oModuleController->updateModuleConfig('message', $config);
				}
			}
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file message.class.php */
/* Location: ./modules/message/message.class.php */
