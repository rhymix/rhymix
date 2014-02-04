<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * SelectExpression
 * Represents an expresion that appears in the select clause
 *
 * 		$column_name can be:
 *  		- a table column name
 *  		- an sql function - like count(*)
 * 	  		- an sql expression - substr(column_name, 1, 8) or score1 + score2
 * 		$column_name is already escaped
 *
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class SelectExpression extends Expression
{

	/**
	 * column alias name
	 * @var string
	 */
	var $column_alias;

	/**
	 * constructor
	 * @param string $column_name
	 * @param string $alias
	 * @return void
	 */
	function SelectExpression($column_name, $alias = NULL)
	{
		parent::Expression($column_name);
		$this->column_alias = $alias;
	}

	/**
	 * Return column expression, ex) column as alias
	 * @return string
	 */
	function getExpression()
	{
		return sprintf("%s%s", $this->column_name, $this->column_alias ? " as " . $this->column_alias : "");
	}

	function show()
	{
		return true;
	}

	function getArgument()
	{
		return null;
	}

	function getArguments()
	{
		return array();
	}

	function isSubquery()
	{
		return false;
	}

}
/* End of file SelectExpression.class.php */
/* Location: ./classes/db/queryparts/expression/SelectExpression.class.php */
