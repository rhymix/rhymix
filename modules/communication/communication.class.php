<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communication
 * @author NAVER (developers@xpressengine.com)
 * communication module of the high class
 */
class Communication extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		// Create a temporary file storage for one new private message notification
		FileHandler::makeDir('./files/member_extra_info/new_message_flags');
	}

	/**
	 * method to check if successfully installed.
	 * @return boolean true : need to update false : don't need to update
	 */
	function checkUpdate()
	{
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
		if(!is_dir("./files/member_extra_info/new_message_flags"))
		{
			FileHandler::makeDir('./files/member_extra_info/new_message_flags');
		}
	}
}
/* End of file communication.class.php */
/* Location: ./modules/comment/communication.class.php */
