<?php 

	class Subquery extends Query {
		var $alias;
		
		function Subquery($alias, $columns, $tables, $conditions, $groups, $orderby, $limit){
			$this->alias = $alias;
			
			$this->queryID = null;
			$this->action = null;
			
			$this->columns = $columns;
			$this->tables = $tables;
			$this->conditions = $conditions;
			$this->groups = $groups;
			$this->orderby = $orderby;
			$this->limit = $limit;
		}
		
		function getAlias(){
			return $this->alias;
		}
	}

?>