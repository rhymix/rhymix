<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminlogging class
 * Base class of adminlogging module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/adminlogging
 * @version 0.1
 */
class adminlogging extends ModuleObject
{

	/**
	 * Install adminlogging module
	 * @return void
	 */
	function moduleInstall()
	{

	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if (!$oDB->isColumnExists('admin_log', 'member_srl'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('admin_log', 'idx_member_srl'))
		{
			return true;
		}

		$column_info = $oDB->getColumnInfo('admin_log', 'request_vars');
		if ($column_info->xetype !== 'bigtext')
		{
			return true;
		}

		return false;
	}

	/**
	 * Update module
	 * @return void
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if (!$oDB->isColumnExists('admin_log', 'member_srl'))
		{
			$oDB->addColumn('admin_log', 'member_srl', 'number', null, 0, true, 'site_srl');
		}
		if (!$oDB->isIndexExists('admin_log', 'idx_member_srl'))
		{
			$oDB->addIndex('admin_log', 'idx_member_srl', ['member_srl']);
		}

		$column_info = $oDB->getColumnInfo('admin_log', 'request_vars');
		if ($column_info->xetype !== 'bigtext')
		{
			$oDB->modifyColumn('admin_log', 'request_vars', 'bigtext');
		}
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{

	}

}
/* End of file adminlogging.class.php */
/* Location: ./modules/adminlogging/adminlogging.class.php */
