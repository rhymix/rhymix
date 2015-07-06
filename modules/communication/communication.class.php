<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communication 
 * @author NAVER (developers@xpressengine.com)
 * communication module of the high class
 */
class communication extends ModuleObject
{
	var $triggers = array(
		array('member.getMemberMenu', 'communication', 'controller', 'triggerBeforeMemberPopupMenu', 'before'),
		array('moduleHandler.init', 'communication', 'controller', 'triggerAddMemberMenu', 'after')
	);

	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		// Create triggers
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		// Create a temporary file storage for one new private message notification
		FileHandler::makeDir('./files/member_extra_info/new_message_flags');

		// Save Default Config.
		$config = new stdClass;
		$config->able_module = 'Y';
		$config->skin = 'default';
		$config->colorset = 'white';
		$config->editor_skin = 'default';
		$communication_config->mskin = 'default';
		$communication_config->grant_write = array('default_grant'=>'member');

		// Save configurations
		$oModuleController->insertModuleConfig('communication', $config);
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
		$oModuleController = getController('module');
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

		// check if module is abled
		if($config->able_module != 'N')
		{
			// Check triggers
			foreach($this->triggers as $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
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
		$oModuleController = getController('module');
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

		// Create triggers
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
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


	function moduleUninstall()
	{
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
		return new Object();
	}
}
/* End of file communication.class.php */
/* Location: ./modules/comment/communication.class.php */
