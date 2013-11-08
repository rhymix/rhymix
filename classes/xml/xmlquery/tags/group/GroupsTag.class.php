<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * GroupsTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/group
 * @version 0.1
 */
class GroupsTag
{

	/**
	 * column list
	 * @var array
	 */
	var $groups;

	/**
	 * constructor
	 * @param array|string $xml_groups
	 * @return void
	 */
	function GroupsTag($xml_groups)
	{
		$this->groups = array();

		if($xml_groups)
		{
			if(!is_array($xml_groups))
			{
				$xml_groups = array($xml_groups);
			}

			$dbParser = &DB::getParser();
			for($i = 0; $i < count($xml_groups); $i++)
			{
				$group = $xml_groups[$i];
				$column = trim($group->attrs->column);
				if(!$column)
				{
					continue;
				}

				$column = $dbParser->parseExpression($column);
				$this->groups[] = $column;
			}
		}
	}

	function toString()
	{
		$output = 'array(' . PHP_EOL;
		foreach($this->groups as $group)
		{
			$output .= "'" . $group . "' ,";
		}
		$output = substr($output, 0, -1);
		$output .= ')';
		return $output;
	}

}
/* End of file GroupsTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/group/GroupsTag.class.php */
