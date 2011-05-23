<?php 
	class ConditionsTag {
		var $condition_groups;
		
		function ConditionsTag($xml_conditions){
			$this->condition_groups = array();
			
			$xml_condition_list = $xml_conditions->condition;
			if($xml_condition_list){
				require_once(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionGroupTag.class.php');
				$this->condition_groups[] = new ConditionGroupTag($xml_condition_list);
			}
			
			$xml_groups = $xml_conditions->group;
			if($xml_groups){
				if(!is_array($xml_groups)) $xml_groups = array($xml_groups);
				require_once(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionGroupTag.class.php');
				foreach($xml_groups as $group){
					$this->condition_groups[] = new ConditionGroupTag($group->condition, $group->pipe);
				}
			}			
		}
		
		function toString(){
			$output_conditions = 'array(' . PHP_EOL;
			foreach($this->condition_groups as $condition){
				$output_conditions .= $condition->getConditionGroupString() . PHP_EOL . ',';
			}
			$output_conditions = substr($output_conditions, 0, -1);
			$output_conditions .= ')';	
			return $output_conditions;					
		}

		function getArguments(){
			$arguments = array();
			foreach($this->condition_groups as $condition){
				$arguments = array_merge($arguments, $condition->getArguments());
			}
			return $arguments;
		}					
	}
?>