<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * ConditionsTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/condition
 * @version 0.1
 */
class ConditionsTag
{

	/**
	 * ConditionGroupTag list
	 * @var array value is ConditionGroupTag object
	 */
	var $condition_groups;

	/**
	 * constructor
	 * @param object $xml_conditions
	 * @return void
	 */
	function ConditionsTag($xml_conditions)
	{
		$this->condition_groups = array();
		if(!$xml_conditions)
		{
			return;
		}

		$xml_condition_list = array();
		if($xml_conditions->condition)
		{
			$xml_condition_list = $xml_conditions->condition;
		}

		if($xml_conditions->query)
		{
			if(!is_array($xml_condition_list))
			{
				$xml_condition_list = array($xml_condition_list);
			}
			if(!is_array($xml_conditions->query))
			{
				$xml_conditions->query = array($xml_conditions->query);
			}
			$xml_condition_list = array_merge($xml_condition_list, $xml_conditions->query);
		}
		if($xml_condition_list)
		{
			$this->condition_groups[] = new ConditionGroupTag($xml_condition_list);
		}

		$xml_groups = $xml_conditions->group;
		if($xml_groups)
		{
			if(!is_array($xml_groups))
			{
				$xml_groups = array($xml_groups);
			}
			foreach($xml_groups as $group)
			{
				$this->condition_groups[] = new ConditionGroupTag($group->condition, $group->attrs->pipe);
			}
		}
	}

	/**
	 * ConditionGroupTag object to string
	 * @return string
	 */
	function toString()
	{
		$output_conditions = 'array(' . PHP_EOL;
		foreach($this->condition_groups as $condition)
		{
			$output_conditions .= $condition->getConditionGroupString() . PHP_EOL . ',';
		}
		$output_conditions = substr($output_conditions, 0, -1);
		$output_conditions .= ')';
		return $output_conditions;
	}

	function getArguments()
	{
		$arguments = array();
		foreach($this->condition_groups as $condition)
		{
			$arguments = array_merge($arguments, $condition->getArguments());
		}
		return $arguments;
	}

}
/* End of file ConditionsTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/condition/ConditionsTag.class.php */
