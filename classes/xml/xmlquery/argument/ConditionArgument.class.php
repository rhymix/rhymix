<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * ConditionArgument class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/argument
 * @version 0.1
 */
class ConditionArgument extends Argument
{

	/**
	 * Operator keyword. for example 'in', 'notint', 'between'
	 * @var string
	 */
	var $operation;

	/**
	 * constructor
	 * @param string $name
	 * @param mixed $value
	 * @param string $operation
	 * @return void
	 */
	function ConditionArgument($name, $value, $operation)
	{
		$operationList = array('in' => 1, 'notin' => 1, 'not_in' => 1, 'between' => 1);
		if(isset($value) && isset($operationList[$operation]) && !is_array($value) && $value != '')
		{
			$value = str_replace(' ', '', $value);
			$value = str_replace('\'', '', $value);
			$value = explode(',', $value);
		}
		parent::Argument($name, $value);
		$this->operation = $operation;
	}

	/**
	 * create condition value. set $this->value
	 * @return void
	 */
	function createConditionValue()
	{
		if(!isset($this->value))
		{
			return;
		}

		$operation = $this->operation;
		$value = $this->value;

		switch($operation)
		{
			case 'like_prefix' :
				if(defined('__CUBRID_VERSION__') && __CUBRID_VERSION__ >= '8.4.1')
				{
					$this->value = '^' . str_replace('%', '(.*)', preg_quote($value));
				}
				else
				{
					$this->value = $value . '%';
				}
				break;
			case 'like_tail' :
				if(defined('__CUBRID_VERSION__') && __CUBRID_VERSION__ >= '8.4.1')
				{
					$this->value = str_replace('%', '(.*)', preg_quote($value)) . '$';
				}
				else
				{
					$this->value = '%' . $value;
				}
				break;
			case 'like' :
				if(defined('__CUBRID_VERSION__') && __CUBRID_VERSION__ >= '8.4.1')
				{
					$this->value = str_replace('%', '(.*)', preg_quote($value));
				}
				else
				{
					$this->value = '%' . $value . '%';
				}
				break;
			case 'notlike' :
				$this->value = '%' . $value . '%';
				break;
			case 'notlike_prefix' :
				$this->value = $value . '%';
				break;
			case 'notlike_tail' :
				$this->value = '%' . $value;
				break;
			case 'in':
				if(!is_array($value))
				{
					$this->value = array($value);
				}
				break;
			case 'notin':
			case 'not_in':
				if(!is_array($value))
				{
					$this->value = array($value);
				}
				break;
		}
	}

	/**
	 * Since ConditionArgument is used in WHERE clause,
	 * where the argument value is compared to a table column,
	 * it is assumed that all arguments have type. There are cases though
	 * where the column does not have any type - if it was removed from
	 * the XML schema for example - see the is_secret column in xe_documents table.
	 * In this case, the column type is retrieved according to argument
	 * value type (using the PHP function is_numeric).
	 *
	 * @return type string
	 */
	function getType()
	{
		if($this->type)
		{
			return $this->type;
		}
		else if(!is_numeric($this->value))
		{
			return 'varchar';
		}
		else
		{
			return '';
		}
	}

	function setColumnType($column_type)
	{
		if(!isset($this->value))
		{
			return;
		}
		if($column_type === '')
		{
			return;
		}

		$this->type = $column_type;
	}

}
/* End of file ConditionArgument.class.php */
/* Location: ./classes/xml/xmlquery/argument/ConditionArgument.class.php */
