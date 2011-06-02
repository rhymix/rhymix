<?php 

	class Query extends Object {
		var $queryID;
		var $action;
		
		var $columns;
		var $tables;
		var $conditions;
		var $groups;
		var $orderby;
		var $limit;
		
		
		function setQueryId($queryID){
			$this->queryID = $queryID;
		}
		
		function setAction($action){
			$this->action = $action;
		}
		
		function setColumns($columns){
			if(!isset($columns) || count($columns) === 0){
				$this->columns = array(new StarExpression());
				return;
			}
			
			if(!is_array($columns)) $columns = array($columns);
			
			$this->columns = $columns;			
		}
		
		function setTables($tables){
			if(!isset($tables) || count($tables) === 0){
				$this->setError(true);
				$this->setMessage("You must provide at least one table for the query.");				
				return;
			}

			if(!is_array($tables)) $tables = array($tables);
			
			$this->tables = $tables;			
		}
		
		function setConditions($conditions){
			if(!isset($conditions) || count($conditions) === 0) return;
			if(!is_array($conditions)) $conditions = array($conditions);
			
			$this->conditions = $conditions;			
		}
		
		function setGroups($groups){
			if(!isset($groups) || count($groups) === 0) return;
			if(!is_array($groups)) $groups = array($groups);
			
			$this->groups = $groups;			
		}
		
		function setOrder($order){
			if(!isset($order) || count($order) === 0) return;
			if(!is_array($order)) $order = array($order);
			
			$this->orderby = $order;			
		}
		
		function setLimit($limit = NULL){
			if(!isset($limit)) return;
			$this->limit = $limit;
		}
		
		// START Fluent interface
		function select($columns= null){
			$this->action = 'select';
			$this->setColumns($columns);			
			return $this;
		}
		
		function from($tables){
			$this->setTables($tables);
			return $this;
		}
		
		function where($conditions){
			$this->setConditions($conditions);
			return $this;	
		}
		
		function groupBy($groups){
			$this->setGroups($groups);
			return $this;				
		}
		
		function orderBy($order){
			$this->setOrder($order);
			return $this;			
		}
		
		function limit($limit){
			$this->setLimit($limit);
			return $this;	
		}
		// END Fluent interface
		
		function getAction(){
			return $this->action;
		}
		
		function getSelectString(){		
			$select = '';
			foreach($this->columns as $column){
				if($column->show())
					$select .= $column->getExpression() . ', ';
			}
			if(trim($select) == '') return '';
			$select = substr($select, 0, -2);
			return $select;
		}
		
		function getUpdateString(){		
			return $this->getSelectString();
		}		
		
		function getInsertString(){		
			$columnsList = '';
			$valuesList = '';
			foreach($this->columns as $column){
				if($column->show()){
					$columnsList .= $column->getColumnName() . ', ';
					$valuesList .= $column->getValue() . ', ';
				}
			}
			$columnsList = substr($columnsList, 0, -2);
			$valuesList = substr($valuesList, 0, -2);
			
			return "($columnsList) \n VALUES ($valuesList)";
		}			
		
		function getFromString(){
			$from = '';
			$simple_table_count = 0;
			foreach($this->tables as $table){
				if($table->isJoinTable() || !$simple_table_count) $from .= $table->toString() . ' ';
				else $from .= ', '.$table->toString() . ' ';
				$simple_table_count++;
			}
			if(trim($from) == '') return '';
			return $from;
		}
		
		function getWhereString(){
			$where = '';
			if(count($this->conditions) > 0){
				foreach($this->conditions as $conditionGroup){
					$where .= $conditionGroup->toString();
				}
				if(trim($where) == '') return '';
				
			}
			return $where;
		}
		
		function getGroupByString(){
			$groupBy = '';
			if($this->groups) if($this->groups[0] !== "")
				$groupBy = implode(', ', $this->groups);
			return $groupBy;									
		}
		
		function getOrderByString(){
			if(count($this->orderby) === 0) return '';
			$orderBy = '';
			foreach($this->orderby as $order){
				$orderBy .= $order->toString() .', ';
			}
			$orderBy = substr($orderBy, 0, -2);
			return $orderBy;
		}
		
		function getLimit(){
			return $this->limit;
		}
		
		function getLimitString(){
			$limit = '';
			if(count($this->limit) > 0){
				$limit = '';
				$limit .= $this->limit->toString();
			}	
			return $limit;		
		}
		
		function getFirstTableName(){
			return $this->tables[0]->getName();			
		}
	}



?>