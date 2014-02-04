<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
require_once(_XE_PATH_.'modules/trash/model/TrashVO.php');

/**
 * trash class
 * trash the module's high class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/trash
 * @version 0.1
 */
class trash extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * A method to check if successfully installed
	 * @return bool
	 */
	function checkUpdate()
	{
		//$oDB = &DB::getInstance();
		//$oModuleModel = getModel('module');

		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		//$oDB = &DB::getInstance();
		//$oModuleModel = getModel('module');

		return new Object(0,'success_updated');
	}
}
/* End of file trash.class.php */
/* Location: ./modules/trash/trash.class.php */
