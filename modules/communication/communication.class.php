<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communication 
 * @author NAVER (developers@xpressengine.com)
 * communication module of the high class
 */
class communication extends ModuleObject
{
	private $triggers = array(
		array('moduleHandler.init', 'communication', 'controller', 'triggerModuleHandlerBefore', 'before'),
		array('member.getMemberMenu', 'communication', 'controller', 'triggerMemberMenu', 'before')
	);
	private $delete_triggers = array(
		array('moduleObject.proc', 'communication', 'controller', 'triggerModuleProcAfter', 'after')
	);
	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		$oModuleController = getController('module');
		
		foreach($this->triggers as $trigger)
		{
			$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		// Create a temporary file storage for one new private message notification
		FileHandler::makeDir('./files/member_extra_info/new_message_flags');
	}

	/**
	 * method to check if successfully installed.
	 * @return boolean true : need to update false : don't need to update
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return TRUE;
			}
		}

		foreach($this->delete_triggers as $trigger)
		{

			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return TRUE;
			}
		}

		if(!is_dir("./files/member_extra_info/new_message_flags"))
		{
			FileHandler::makeDir('./files/member_extra_info/new_message_flags');
			if(!is_dir("./files/member_extra_info/new_message_flags"))
			{
				return TRUE;
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
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		foreach($this->delete_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		if(!is_dir("./files/member_extra_info/new_message_flags"))
		{
			FileHandler::makeDir('./files/member_extra_info/new_message_flags');
		}
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
