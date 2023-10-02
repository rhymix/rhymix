<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  poll
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the poll module
 */
class Poll extends ModuleObject
{
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Set the default skin
		$config = new stdClass;
		$config->skin = 'default';
		$config->colorset = 'normal';
		$oModuleController = ModuleController::getInstance();
		$oModuleController->insertModuleConfig('poll', $config);
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();

		if(!$oDB->isColumnExists('poll', 'poll_type'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('poll_log','poll_item'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('poll_item','add_user_srl'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();

		if(!$oDB->isColumnExists('poll','poll_type'))
		{
			$oDB->addColumn('poll', 'poll_type', 'number', 11, 0);
		}

		if(!$oDB->isColumnExists('poll_log','poll_item'))
		{
			$oDB->addColumn('poll_log', 'poll_item', 'varchar', 250, 0);
		}

		if(!$oDB->isColumnExists('poll_item','add_user_srl'))
		{
			$oDB->addColumn('poll_item', 'add_user_srl', 'number', 11, 0);
		}
	}

	/**
	 * @brief Check if this poll could display member information
	 */
	function checkMemberInfo($type)
	{
		return ($type==1 || $type==3);
	}

	/**
	 * @brief Check if the items of this poll could be added by members.
	 */
	function isAbletoAddItem($type)
	{
		return ($type==2 || $type==3);
	}
}
/* End of file poll.class.php */
/* Location: ./modules/poll/poll.class.php */
