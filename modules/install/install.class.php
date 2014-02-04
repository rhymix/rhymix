<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  install
 * @author NAVER (developers@xpressengine.com)
 * @brief install module of the high class
 */
class install extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		return new Object();
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file install.class.php */
/* Location: ./modules/install/install.class.php */
