<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * DeleteExpression
 *
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 * @todo Fix this class 
 */
class DeleteExpression extends Expression
{

	/**
	 * column value
	 * @var mixed
	 */
	var $value;

	/**
	 * constructor
	 * @param string $column_name
	 * @param mixed $value
	 * @return void
	 */
	function DeleteExpression($column_name, $value)
	{
		parent::Expression($column_name);
		$this->value = $value;
	}

	/**
	 * Return column expression, ex) column = value
	 * @return string
	 */
	function getExpression()
	{
		return "$this->column_name = $this->value";
	}

	function getValue()
	{
		// TODO Escape value according to column type instead of variable type
		if(!is_numeric($this->value))
		{
			return "'" . $this->value . "'";
		}
		return $this->value;
	}

	function show()
	{
		if(!$this->value)
		{
			return false;
		}
		return true;
	}

}
/* End of file DeleteExpression.class.php */
/* Location: ./classes/db/queryparts/expression/DeleteExpression.class.php */
