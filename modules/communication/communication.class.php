<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communication 
 * @author NAVER (developers@xpressengine.com)
 * communication module of the high class
 */
class communication extends ModuleObject
{

	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		// Create a temporary file storage for one new private message notification
		FileHandler::makeDir('./files/member_extra_info/new_message_flags');
		return new Object();
	}

	/**
	 * method to check if successfully installed.
	 * @return boolean true : need to update false : don't need to update
	 */
	function checkUpdate()
	{
		if(!is_dir("./files/member_extra_info/new_message_flags"))
		{
			return TRUE;
		}

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if(count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/communication/', $config_parse[0]);
				if(is_dir($template_path))
				{
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Update
	 * @return Object
	 */
	function moduleUpdate()
	{
		if(!is_dir("./files/member_extra_info/new_message_flags"))
		{
			FileHandler::makeDir('./files/member_extra_info/new_message_flags');
		}

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');
		if(!is_object($config))
		{
			$config = new stdClass();
		}

		if($config->skin)
		{
			$config_parse = explode('.', $config->skin);
			if(count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/communication/', $config_parse[0]);
				if(is_dir($template_path))
				{
					$config->skin = implode('|@|', $config_parse);
					$oModuleController = getController('module');
					$oModuleController->updateModuleConfig('communication', $config);
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
		
	}

}
/* End of file communication.class.php */
/* Location: ./modules/comment/communication.class.php */
