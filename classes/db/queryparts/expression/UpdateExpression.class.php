<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * UpdateExpression
 *
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class UpdateExpression extends Expression
{

	/**
	 * argument
	 * @var object
	 */
	var $argument;

	/**
	 * constructor
	 * @param string $column_name
	 * @param object $argument
	 * @return void
	 */
	function UpdateExpression($column_name, $argument)
	{
		parent::Expression($column_name);
		$this->argument = $argument;
	}

	/**
	 * Return column expression, ex) column = value
	 * @return string
	 */
	function getExpression($with_value = true)
	{
		if($with_value)
		{
			return $this->getExpressionWithValue();
		}
		return $this->getExpressionWithoutValue();
	}

	/**
	 * Return column expression, ex) column = value
	 * @return string
	 */
	function getExpressionWithValue()
	{
		$value = $this->argument->getValue();
		$operation = $this->argument->getColumnOperation();
		if(isset($operation))
		{
			return "$this->column_name = $this->column_name $operation $value";
		}
		return "$this->column_name = $value";
	}

	/**
	 * Return column expression, ex) column = ?
	 * Can use prepare statement
	 * @return string
	 */
	function getExpressionWithoutValue()
	{
		$operation = $this->argument->getColumnOperation();
		if(isset($operation))
		{
			return "$this->column_name = $this->column_name $operation ?";
		}
		return "$this->column_name = ?";
	}

	function getValue()
	{
		// TODO Escape value according to column type instead of variable type
		$value = $this->argument->getValue();
		if(!is_numeric($value))
		{
			return "'" . $value . "'";
		}
		return $value;
	}

	function show()
	{
		if(!$this->argument)
		{
			return false;
		}
		$value = $this->argument->getValue();
		if(!isset($value))
		{
			return false;
		}
		return true;
	}

	function getArgument()
	{
		return $this->argument;
	}

	function getArguments()
	{
		if($this->argument)
		{
			return array($this->argument);
		}
		else
		{
			return array();
		}
	}

}
/* End of file UpdateExpression.class.php */
/* Location: ./classes/db/queryparts/expression/UpdateExpression.class.php */
