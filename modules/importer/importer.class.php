<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * importer
 * high class of importer module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/importer
 * @version 0.1
 */
class importer extends ModuleObject
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
		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		return new Object();
	}

	/**
	 * Re-generate the cache file
	 * @return void
	 */
	function recompileCache()
	{
	}
}
/* End of file importer.class.php */
/* Location: ./modules/importer/importer.class.php */
