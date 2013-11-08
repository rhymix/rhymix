<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Expression
 * Represents an expression used in select/update/insert/delete statements
 * 
 *  Examples (expressions are inside double square brackets):
 *  	select [[columnA]], [[columnB as aliasB]] from tableA
 *  	update tableA set [[columnA = valueA]] where columnB = something
 *
 * @author Corina
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class Expression
{

	/**
	 * column name
	 * @var string
	 */
	var $column_name;

	/**
	 * constructor
	 * @param string $column_name
	 * @return void
	 */
	function Expression($column_name)
	{
		$this->column_name = $column_name;
	}

	function getColumnName()
	{
		return $this->column_name;
	}

	function show()
	{
		return false;
	}

	/**
	 * Return column expression, ex) column as alias
	 * @return string
	 */
	function getExpression()
	{
		
	}

}
/* End of file Expression.class.php */
/* Location: ./classes/db/queryparts/expression/Expression.class.php */
