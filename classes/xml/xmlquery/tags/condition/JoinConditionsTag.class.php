<?php 
	/**
	 * JoinConditionsTag class
	 *
	 * @author Corina
	 * @package /classes/xml/xmlquery/tags/condition
	 * @version 0.1
	 */
	class JoinConditionsTag extends ConditionsTag {
		/**
		 * constructor
		 * @param object $xml_conditions
		 * @return void
		 */
		function JoinConditionsTag($xml_conditions){
			parent::ConditionsTag($xml_conditions);
			$this->condition_groups[0]->conditions[0]->setPipe("");
		}
	}

?>
