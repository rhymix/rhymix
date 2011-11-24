<?php 

	class JoinConditionsTag extends ConditionsTag {
		
		function JoinConditionsTag($xml_conditions){
			parent::ConditionsTag($xml_conditions);
			$this->condition_groups[0]->conditions[0]->setPipe("");
		}
	}

?>