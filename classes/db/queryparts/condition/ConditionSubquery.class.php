<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/condition
 * @version 0.1
 */
class ConditionSubquery extends Condition
{

	/**
	 * constructor
	 * @param string $column_name
	 * @param mixed $argument
	 * @param string $operation
	 * @param string $pipe
	 * @return void
	 */
	function ConditionSubquery($column_name, $argument, $operation, $pipe = "")
	{
		parent::Condition($column_name, $argument, $operation, $pipe);
		$this->_value = $this->argument->toString();
	}

}
/* End of file ConditionSubquery.class.php */
/* Location: ./classes/db/queryparts/condition/ConditionSubquery.class.php */
