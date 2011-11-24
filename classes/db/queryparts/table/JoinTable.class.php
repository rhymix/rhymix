<?php 

	/**
	 * @class JoinTable
	 * @author Arnia Software
	 * @brief 
	 * 
	 * @remarks
	 * 		$conditions in an array of Condition objects 
	 *
	 */

	class JoinTable extends Table {
		var $join_type;
		var $conditions;
		
		function JoinTable($name, $alias, $join_type, $conditions){
			parent::Table($name, $alias);
			$this->join_type = $join_type;
			$this->conditions = $conditions;
		}
		
		function toString($with_value = true){
			$part = $this->join_type . ' ' . $this->name ;
			$part .= $this->alias ? ' as ' . $this->alias : '';
			$part .= ' on ';
			foreach($this->conditions as $conditionGroup)
				$part .= $conditionGroup->toString($with_value);
			return $part;
		}
		
		function isJoinTable(){
			return true;
		}
	}

?>