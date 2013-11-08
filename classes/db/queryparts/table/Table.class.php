<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class Table
{

	/**
	 * table name
	 * @var string
	 */
	var $name;

	/**
	 * table alias
	 * @var string
	 */
	var $alias;

	/**
	 * constructor
	 * @param string $name
	 * @param string $alias
	 * @return void
	 */
	function Table($name, $alias = NULL)
	{
		$this->name = $name;
		$this->alias = $alias;
	}

	function toString()
	{
		//return $this->name;
		return sprintf("%s%s", $this->name, $this->alias ? ' as ' . $this->alias : '');
	}

	function getName()
	{
		return $this->name;
	}

	function getAlias()
	{
		return $this->alias;
	}

	function isJoinTable()
	{
		return false;
	}

}
/* End of file Table.class.php */
/* Location: ./classes/db/queryparts/table/Table.class.php */
