<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/condition
 * @version 0.1
 */
class ConditionGroup
{

	/**
	 * condition list
	 * @var array
	 */
	var $conditions;

	/**
	 * pipe can use 'and', 'or'...
	 * @var string
	 */
	var $pipe;
	var $_group;
	var $_show;

	/**
	 * constructor
	 * @param array $conditions
	 * @param string $pipe
	 * @return void
	 */
	function ConditionGroup($conditions, $pipe = "")
	{
		$this->conditions = array();
		foreach($conditions as $condition)
		{
			if($condition->show())
			{
				$this->conditions[] = $condition;
			}
		}
		if(count($this->conditions) === 0)
		{
			$this->_show = false;
		}
		else
		{
			$this->_show = true;
		}

		$this->pipe = $pipe;
	}

	function show()
	{
		return $this->_show;
	}

	function setPipe($pipe)
	{
		if($this->pipe !== $pipe)
		{
			$this->_group = null;
		}
		$this->pipe = $pipe;
	}

	/**
	 * value to string
	 * @param boolean $with_value
	 * @return string
	 */
	function toString($with_value = true)
	{
		if(!isset($this->_group))
		{
			$cond_indx = 0;
			$group = '';

			foreach($this->conditions as $condition)
			{
				if($cond_indx === 0)
				{
					$condition->setPipe("");
				}
				$group .= $condition->toString($with_value) . ' ';
				$cond_indx++;
			}

			if($this->pipe !== "" && trim($group) !== '')
			{
				$group = $this->pipe . ' (' . $group . ')';
			}

			$this->_group = $group;
		}
		return $this->_group;
	}

	/**
	 * return argument list
	 * @return array
	 */
	function getArguments()
	{
		$args = array();
		foreach($this->conditions as $condition)
		{
			$arg = $condition->getArgument();
			if($arg)
			{
				$args[] = $arg;
			}
		}
		return $args;
	}

}
/* End of file ConditionGroup.class.php */
/* Location: ./classes/db/queryparts/condition/ConditionGroup.class.php */
