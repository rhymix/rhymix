<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * DefaultValue class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/queryargument
 * @version 0.1
 */
class DefaultValue
{

	/**
	 * Column name
	 * @var string
	 */
	var $column_name;

	/**
	 * Value
	 * @var mixed
	 */
	var $value;

	/**
	 * sequnence status
	 * @var bool
	 */
	var $is_sequence = FALSE;

	/**
	 * operation status
	 * @var bool
	 */
	var $is_operation = FALSE;

	/**
	 * operation
	 * @var string
	 */
	var $operation = '';

	/**
	 * Checks if value is plain string or name of XE function (ipaddress, plus, etc).
	 * @var bool
	 */
	var $_is_string = FALSE;

	/**
	 * Checks if value is string resulted from evaluating a piece of PHP code (see $_SERVER[REMOTE_ADDR])
	 * @var bool
	 */
	var $_is_string_from_function = FALSE;

	/**
	 * constructor
	 * @param string $column_name column name
	 * @param mixed $value value
	 * @return void
	 */
	function DefaultValue($column_name, $value)
	{
		$dbParser = DB::getParser();
		$this->column_name = $dbParser->parseColumnName($column_name);
		$this->value = $value;
		$this->value = $this->_setValue();
	}

	function isString()
	{
		return $this->_is_string;
		$str_pos = strpos($this->value, '(');
		if($str_pos === false)
		{
			return TRUE;
		}
		return FALSE;
	}

	function isStringFromFunction()
	{
		return $this->_is_string_from_function;
	}

	function isSequence()
	{
		return $this->is_sequence;
	}

	function isOperation()
	{
		return $this->is_operation;
	}

	function getOperation()
	{
		return $this->operation;
	}

	function _setValue()
	{
		if(!isset($this->value))
		{
			return;
		}

		// If value contains comma separated values and does not contain paranthesis
		//  -> default value is an array
		if(strpos($this->value, ',') !== FALSE && strpos($this->value, '(') === FALSE)
		{
			return sprintf('array(%s)', $this->value);
		}

		$str_pos = strpos($this->value, '(');
		// // TODO Replace this with parseExpression
		if($str_pos === FALSE)
		{
			$this->_is_string = TRUE;
			return '\'' . $this->value . '\'';
		}
		//if($str_pos===false) return $this->value;

		$func_name = substr($this->value, 0, $str_pos);
		$args = substr($this->value, $str_pos + 1, -1);

		switch($func_name)
		{
			case 'ipaddress' :
				$val = '$_SERVER[\'REMOTE_ADDR\']';
				$this->_is_string_from_function = TRUE;
				break;
			case 'unixtime' :
				$val = '$_SERVER[\'REQUEST_TIME\']';
				break;
			case 'curdate' :
				$val = 'date("YmdHis")';
				$this->_is_string_from_function = TRUE;
				break;
			case 'sequence' :
				$this->is_sequence = TRUE;
				$val = '$sequence';
				break;
			case 'plus' :
				$args = abs($args);
				$this->is_operation = TRUE;
				$this->operation = '+';
				$val = sprintf('%d', $args);
				break;
			case 'minus' :
				$args = abs($args);
				$this->is_operation = TRUE;
				$this->operation = '-';
				$val = sprintf('%d', $args);
				break;
			case 'multiply' :
				$args = intval($args);
				$this->is_operation = TRUE;
				$this->operation = '*';
				$val = sprintf('%d', $args);
				break;
			default :
				$val = '\'' . $this->value . '\'';
			//$val = $this->value;
		}

		return $val;
	}

	function toString()
	{
		return $this->value;
	}

}
/* End of file DefaultValue.class.php */
/* Location: ./classes/xml/xmlquery/queryargument/DefaultValue.class.php */
