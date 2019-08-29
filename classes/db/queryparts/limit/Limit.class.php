<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/limit
 * @version 0.1
 */
class Limit
{

	/**
	 * start number
	 * @var int
	 */
	var $start;

	/**
	 * list count
	 * @var int
	 */
	var $list_count;

	/**
	 * page count
	 * @var int
	 */
	var $page_count;

	/**
	 * current page
	 * @var int
	 */
	var $page;

	/**
	 * constructor
	 * @param int $list_count
	 * @param int $page
	 * @param int $page_count
	 * @param int $offset
	 * @return void
	 */
	function __construct($list_count, $page = NULL, $page_count = NULL, $offset = NULL)
	{
		$this->list_count = $list_count;
		if($list_count->getValue())
		{
			if($page && $page->getValue())
			{
				$this->start = ($page->getValue() - 1) * $list_count->getValue();
				$this->page_count = $page_count;
				$this->page = $page;
			}
			elseif($offset)
			{
				$this->start = $offset->getValue();
			}
		}
	}

	/**
	 * In case you choose to use query limit in other cases than page select
	 * @return boolean
	 */
	function isPageHandler()
	{
		if($this->page)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function getOffset()
	{
		return $this->start;
	}

	function getLimit()
	{
		return $this->list_count->getValue();
	}

	function toString()
	{
		if($this->start)
		{
			return $this->start . ' , ' . $this->list_count->getValue();
		}
		else
		{
			return $this->list_count->getValue() ?: '';
		}
	}
}
/* End of file Limit.class.php */
/* Location: ./classes/db/limit/Limit.class.php */
