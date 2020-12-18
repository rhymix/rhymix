<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
require_once(RX_BASEDIR.'modules/trash/model/TrashVO.php');

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
		
	}

	/**
	 * A method to check if successfully installed
	 * @return bool
	 */
	function checkUpdate()
	{
		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		
	}
}
/* End of file trash.class.php */
/* Location: ./modules/trash/trash.class.php */
