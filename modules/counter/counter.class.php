<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * High class of counter module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class counter extends ModuleObject
{

	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		
	}

	/**
	 * method if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if ($oDB->isTableExists('counter_site_status'))
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Module update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if ($oDB->isTableExists('counter_site_status'))
		{
			$oDB->dropTable('counter_site_status');
		}
	}

	/**
	 * re-generate the cache file
	 *
	 * @return Object
	 */
	function recompileCache()
	{
		
	}

}
/* End of file counter.class.php */
/* Location: ./modules/counter/counter.class.php */
