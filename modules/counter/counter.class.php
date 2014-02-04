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
		$oCounterController = getController('counter');

		// add a row for the total visit history 
		//$oCounterController->insertTotalStatus();

		// add a row for today's status
		//$oCounterController->insertTodayStatus();

		return new Object();
	}

	/**
	 * method if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		// Add site_srl to the counter
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('counter_log', 'site_srl'))
		{
			return TRUE;
		}

		if(!$oDB->isIndexExists('counter_log', 'idx_site_counter_log'))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Module update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		// Add site_srl to the counter
		$oDB = DB::getInstance();

		if(!$oDB->isColumnExists('counter_log', 'site_srl'))
		{
			$oDB->addColumn('counter_log', 'site_srl', 'number', 11, 0, TRUE);
		}

		if(!$oDB->isIndexExists('counter_log', 'idx_site_counter_log'))
		{
			$oDB->addIndex('counter_log', 'idx_site_counter_log', array('site_srl', 'ipaddress'), FALSE);
		}

		return new Object(0, 'success_updated');
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
