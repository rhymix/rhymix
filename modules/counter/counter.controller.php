<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Counter module's controller class
 *
 * @author NAVER (developers@xpressengine.com)
 */
class counterController extends counter
{

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Counter logs.
	 * If want use below function, you can use 'counterExecute' function instead this function
	 *
	 * @return void
	 */
	function procCounterExecute()
	{

	}

	/**
	 * Counter logs
	 *
	 * @return void
	 */
	function counterExecute()
	{
		if(CounterModel::isLogged())
		{
			$this->insertPageView();
		}
		else
		{
			$this->insertUniqueVisitor();
		}
	}

	/**
	 * Leave logs
	 *
	 * @return void
	 */
	public function insertLog()
	{
		$args = new stdClass();
		$args->regdate = date('YmdHis');
		$args->user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 250);
		$args->site_srl = 0;
		executeQuery('counter.insertCounterLog', $args);
	}

	/**
	 * Register the unique visitor
	 *
	 * @return void
	 */
	public function insertUniqueVisitor()
	{
		$oDB = DB::getInstance();
		$oDB->begin();
		
		$args = new stdClass();
		$args->regdate = [0, $date = date('Ymd')];
		executeQuery('counter.updateCounterUnique', $args);
		
		$affected_rows = $oDB->getAffectedRows();
		if ($affected_rows == 1)
		{
			$args = new stdClass;
			$args->regdate = $date;
			executeQuery('counter.insertTodayStatus', $args);
		}
		if ($affected_rows == 0)
		{
			$args = new stdClass;
			$args->regdate = 0;
			executeQuery('counter.insertTodayStatus', $args);
		}
		
		$this->insertLog();
		
		$oDB->commit();
	}

	/**
	 * Register pageview
	 *
	 * @return void
	 */
	public function insertPageView()
	{
		$args = new stdClass;
		$args->regdate = [0, date('Ymd')];
		executeQuery('counter.updateCounterPageview', $args);
	}

	/**
	 * @deprecated
	 */
	public function insertTodayStatus()
	{
		$this->insertUniqueVisitor();
	}

	/**
	 * @deprecated
	 */
	public function insertTotalStatus()
	{
		
	}
	
	/**
	 * @deprecated
	 */
	public function deleteSiteCounterLogs()
	{
		
	}
}
/* End of file counter.controller.php */
/* Location: ./modules/counter/counter.controller.php */
