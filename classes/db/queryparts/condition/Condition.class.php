<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/condition
 * @version 0.1
 */
class Condition
{

	/**
	 * column name
	 * @var string
	 */
	var $column_name;
	var $argument;

	/**
	 * operation can use 'equal', 'more', 'excess', 'less', 'below', 'like_tail', 'like_prefix', 'like', 'notlike_tail',
	 * 'notlike_prefix', 'notlike', 'in', 'notin', 'not_in', 'and', 'or', 'xor', 'not', 'notequal', 'between'
	 * 'null', 'notnull'
	 * @var string
	 */
	var $operation;

	/**
	 * pipe can use 'and', 'or'...
	 * @var string
	 */
	var $pipe;
	var $_value;
	var $_show;
	var $_value_to_string;

	/**
	 * constructor
	 * @param string $column_name
	 * @param mixed $argument
	 * @param string $operation
	 * @param string $pipe
	 * @return void
	 */
	function __construct($column_name, $argument, $operation, $pipe = 'and')
	{
		$this->column_name = $column_name;
		$this->argument = $argument;
		$this->operation = $operation;
		$this->pipe = $pipe;
	}

	function getArgument()
	{
		return null;
	}

	/**
	 * value to string
	 * @param boolean $withValue
	 * @return string
	 */
	function toString($withValue = true)
	{
		if(!isset($this->_value_to_string))
		{
			if(!$this->show())
			{
				$this->_value_to_string = '';
			}
			else if($withValue)
			{
				$this->_value_to_string = $this->toStringWithValue();
			}
			else
			{
				$this->_value_to_string = $this->toStringWithoutValue();
			}
		}
		return $this->_value_to_string;
	}

	/**
	 * change string without value
	 * @return string
	 */
	function toStringWithoutValue()
	{
		return strtoupper($this->pipe) . ' ' . $this->getConditionPart($this->_value);
	}

	/**
	 * change string with value
	 * @return string
	 */
	function toStringWithValue()
	{
		return strtoupper($this->pipe) . ' ' . $this->getConditionPart($this->_value);
	}

	function setPipe($pipe)
	{
		$this->pipe = $pipe;
	}

	/**
	 * @return boolean
	 */
	function show()
	{
		if(!isset($this->_show))
		{
			if(is_array($this->_value) && count($this->_value) === 1 && $this->_value[0] === '')
			{
				$this->_show = false;
			}
			else
			{
				$this->_show = true;
				switch($this->operation)
				{
					case 'equal' :
					case 'more' :
					case 'excess' :
					case 'less' :
					case 'below' :
					case 'gte' :
					case 'gt' :
					case 'lte' :
					case 'lt' :
					case 'like_tail' :
					case 'like_prefix' :
					case 'like' :
					case 'notlike_tail' :
					case 'notlike_prefix' :
					case 'notlike' :
					case 'not_like' :
					case 'in' :
					case 'notin' :
					case 'not_in' :
					case 'and':
					case 'or':
					case 'xor':
					case 'not':
					case 'notequal' :
					case 'not_equal' :
						// if variable is not set or is not string or number, return
						if(!isset($this->_value))
						{
							$this->_show = false;
							break;
						}
						if($this->_value === '')
						{
							$this->_show = false;
							break;
						}
						$tmpArray = array('string' => 1, 'integer' => 1);
						if(!isset($tmpArray[gettype($this->_value)]))
						{
							$this->_show = false;
							break;
						}
						break;
					case 'between' :
						if(!is_array($this->_value))
						{
							$this->_show = false;
							break;
						}
						if(count($this->_value) != 2)
						{
							$this->_show = false;
							break;
						}
					case 'null':
					case 'notnull':
					case 'not_null':
						break;
					default:
						// If operation is not one of the above, means the condition is invalid
						$this->_show = false;
				}
			}
		}
		return $this->_show;
	}

	/**
	 * Return condition string
	 * @param int|string|array $value
	 * @return string
	 */
	function getConditionPart($value)
	{
		$name = $this->column_name;
		$operation = $this->operation;

		switch($operation)
		{
			case 'equal' :
				return $name . ' = ' . $value;
				break;
			case 'more' :
			case 'gte' :
				return $name . ' >= ' . $value;
				break;
			case 'excess' :
			case 'gt' :
				return $name . ' > ' . $value;
				break;
			case 'less' :
			case 'lte' :
				return $name . ' <= ' . $value;
				break;
			case 'below' :
			case 'lt' :
				return $name . ' < ' . $value;
				break;
			case 'like_tail' :
			case 'like_prefix' :
			case 'like' :
				return $name . ' LIKE ' . $value;
			case 'notlike_tail' :
			case 'notlike_prefix' :
			case 'notlike' :
			case 'not_like' :
				return $name . ' NOT LIKE ' . $value;
				break;
			case 'in' :
				return $name . ' IN ' . $value;
				break;
			case 'notin' :
			case 'not_in' :
				return $name . ' NOT IN ' . $value;
				break;
			case 'notequal' :
			case 'not_equal' :
				return $name . ' <> ' . $value;
				break;
			case 'notnull' :
			case 'not_null' :
				return $name . ' IS NOT NULL ';
				break;
			case 'null' :
				return $name . ' IS NULL ';
				break;
			case 'and' :
				return $name . ' & ' . $value;
				break;
			case 'or' :
				return $name . ' | ' . $value;
				break;
			case 'xor' :
				return $name . ' ^ ' . $value;
				break;
			case 'not' :
				return $name . ' ~ ' . $value;
				break;
			case 'between' :
				return $name . ' BETWEEN ' . $value[0] . ' AND ' . $value[1];
				break;
		}
	}

}
/* End of file Condition.class.php */
/* Location: ./classes/db/queryparts/condition/Condition.class.php */
