<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/condition
 * @version 0.1
 */
class ConditionWithArgument extends Condition
{

	/**
	 * constructor
	 * @param string $column_name
	 * @param mixed $argument
	 * @param string $operation
	 * @param string $pipe
	 * @return void
	 */
	function ConditionWithArgument($column_name, $argument, $operation, $pipe = "")
	{
		if($argument === null)
		{
			$this->_show = false;
			return;
		}
		parent::Condition($column_name, $argument, $operation, $pipe);
		$this->_value = $argument->getValue();
	}

	function getArgument()
	{
		if(!$this->show())
			return;
		return $this->argument;
	}

	/**
	 * change string without value
	 * @return string
	 */
	function toStringWithoutValue()
	{
		$value = $this->argument->getUnescapedValue();

		if(is_array($value))
		{
			$q = '';
			foreach($value as $v)
			{
				$q .= '?,';
			}
			if($q !== '')
			{
				$q = substr($q, 0, -1);
			}
			$q = '(' . $q . ')';
		}
		else
		{
			// Prepared statements: column names should not be sent as query arguments, but instead concatenated to query string
			if($this->argument->isColumnName())
			{
				$q = $value;
			}
			else
			{
				$q = '?';
			}
		}
		return $this->pipe . ' ' . $this->getConditionPart($q);
	}

	/**
	 * @return boolean
	 */
	function show()
	{
		if(!isset($this->_show))
		{
			if(!$this->argument->isValid())
			{
				$this->_show = false;
			}
			if($this->_value === '\'\'')
			{
				$this->_show = false;
			}
			if(!isset($this->_show))
			{
				return parent::show();
			}
		}
		return $this->_show;
	}

}
/* End of file ConditionWithArgument.class.php */
/* Location: ./classes/db/queryparts/condition/ConditionWithArgument.class.php */
