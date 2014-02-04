<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * ClickCountExpression
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class ClickCountExpression extends SelectExpression
{

	/**
	 * click count
	 * @var bool
	 */
	var $click_count;

	/**
	 * constructor
	 * @param string $column_name
	 * @param string $alias
	 * @param bool $click_count
	 * @return void
	 */
	function ClickCountExpression($column_name, $alias = NULL, $click_count = false)
	{
		parent::SelectExpression($column_name, $alias);

		if(!is_bool($click_count))
		{
			// error_log("Click_count value for $column_name was not boolean", 0);
			$this->click_count = false;
		}
		$this->click_count = $click_count;
	}

	function show()
	{
		return $this->click_count;
	}

	/**
	 * Return column expression, ex) column = column + 1
	 * @return string
	 */
	function getExpression()
	{
		$db_type = Context::getDBType();
		if($db_type == 'cubrid')
		{
			return "INCR($this->column_name)";
		}
		else
		{
			return "$this->column_name";
		}
	}

}
/* End of file ClickCountExpression.class.php */
/* Location: ./classes/db/queryparts/expression/ClickCountExpression.class.php */
