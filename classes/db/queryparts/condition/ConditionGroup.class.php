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
				$group = $this->pipe .' (';
			else $group = '';
			
			$cond_indx = 0;
			
			foreach($this->conditions as $condition){
				if($condition->show()){
					if($cond_indx === 0) $condition->setPipe("");
					$group .= $condition->toString() . ' ';
					$cond_indx++;
				}
			}
			// If the group has no conditions in it, return ''
			if($cond_indx === 0) return '';
			
			if($this->pipe !== "")
				$group .= ')';
				
			return $group;
		}
	}
?>