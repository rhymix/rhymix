<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * JoinConditionsTag class
 *
 * @author Corina
 * @package /classes/xml/xmlquery/tags/condition
 * @version 0.1
 */
class JoinConditionsTag extends ConditionsTag
{

	/**
	 * constructor
	 * @param object $xml_conditions
	 * @return void
	 */
	function __construct($xml_conditions)
	{
		parent::__construct($xml_conditions);
		$this->condition_groups[0]->conditions[0]->setPipe("");
	}

}
/* End of file JoinConditionsTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/condition/JoinConditionsTag.class.php */
