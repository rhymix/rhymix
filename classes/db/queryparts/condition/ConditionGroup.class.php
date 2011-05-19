<?php

	class ConditionGroup {
		var $conditions;
		var $pipe;
		
		function ConditionGroup($conditions, $pipe = ""){
			$this->conditions = $conditions;
			$this->pipe = $pipe;
		}
		
		function toString(){
			if($this->pipe !== "")
				$group = $this->pipe .'(';
			else $group = '';
			
			foreach($this->conditions as $condition){
				$group .= $condition->toString() . ' ';
			}
			
			if($this->pipe !== "")
				$group .= ')';
				
			return $group;
		}
	}
?>