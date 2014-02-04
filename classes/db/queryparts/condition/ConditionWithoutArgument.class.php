<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/condition
 * @version 0.1
 */
class ConditionWithoutArgument extends Condition
{

	/**
	 * constructor
	 * @param string $column_name
	 * @param mixed $argument
	 * @param string $operation
	 * @param string $pipe
	 * @return void
	 */
	function ConditionWithoutArgument($column_name, $argument, $operation, $pipe = "")
	{
		parent::Condition($column_name, $argument, $operation, $pipe);
		$tmpArray = array('in' => 1, 'notin' => 1, 'not_in' => 1);
		if(isset($tmpArray[$operation]))
		{
			if(is_array($argument))
			{
				$argument = implode($argument, ',');
			}
			$this->_value = '(' . $argument . ')';
		}
		else
		{
			$this->_value = $argument;
		}
	}

}
/* End of file ConditionWithoutArgument.class.php */
/* Location: ./classes/db/queryparts/condition/ConditionWithoutArgument.class.php */
