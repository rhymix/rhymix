<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * UpdateExpression
 *
 * @author Arnia Software
 * @package /classes/db/queryparts/expression
 * @version 0.1
 */
class UpdateExpressionWithoutArgument extends UpdateExpression
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
	function UpdateExpressionWithoutArgument($column_name, $argument)
	{
		parent::Expression($column_name);
		$this->argument = $argument;
	}

	function getExpression($with_value = true)
	{
		return "$this->column_name = $this->argument";
	}

	function getValue()
	{
		// TODO Escape value according to column type instead of variable type
		$value = $this->argument;
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
		$value = $this->argument;
		if(!isset($value))
		{
			return false;
		}
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

}
/* End of file UpdateExpressionWithoutArgument.class.php */
/* Location: ./classes/db/queryparts/expression/UpdateExpressionWithoutArgument.class.php */
