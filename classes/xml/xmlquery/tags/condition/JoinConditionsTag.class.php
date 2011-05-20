<?php 

	class JoinConditionsTag extends ConditionsTag {
		
		function JoinConditionsTag($xml_conditions, $dbParser){
			parent::ConditionsTag($xml_conditions, $dbParser);
			$this->condition_groups[0]->conditions[0]->setPipe("");
		}
	}

?>