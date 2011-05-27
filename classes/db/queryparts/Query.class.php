<?php 

	class Query extends Object {
		
		var $action;
		var $columns;
		var $tables;
		var $conditions;
		var $groups;
		var $orderby;
		
		function select($columns= null){
			$this->action = 'select';
			if(!isset($columns) || count($columns) === 0){
				$this->columns = array(new StarExpression());
				return $this;
			}
			
			if(!is_array($columns)) $columns = array($columns);
			
			$this->columns = $columns;
			return $this;
		}
		
		function from($tables){
			if(!isset($tables) || count($tables) === 0){
				$this->setError(true);
				$this->setMessage("You must provide at least one table for the query.");				
				return $this;
			}

			if(!is_array($tables)) $tables = array($tables);
			
			$this->tables = $tables;
			return $this;
		}
		
		function where($conditions){
			if(!isset($conditions) || count($conditions) === 0) return $this;
			if(!is_array($conditions)) $conditions = array($conditions);
			
			$this->conditions = $conditions;
			return $this;			
		}
		
		function groupBy($groups){
			if(!isset($groups) || count($groups) === 0) return $this;
			if(!is_array($groups)) $groups = array($groups);
			
			$this->groups = $groups;
			return $this;				
		}
		
		function orderBy($order){
			if(!isset($order) || count($order) === 0) return $this;
			if(!is_array($order)) $order = array($order);
			
			$this->orderby = $order;
			return $this;				
		}
		
		function limit($limit){
			if(!isset($limit)) return $this;
			$this->limit = $limit;
			return $this;
		}

		function getSql(){
			if($this->action == 'select') return $this->getSelectSql();
		}
		
		function getSelectSql(){
			$query = '';
			
			$select = 'SELECT ';
			foreach($this->columns as $column){
				if($column->show())
					$select .= $column->getExpression() . ', ';
			}
			$select = substr($select, 0, -2);
			
			$from = 'FROM ';
			$simple_table_count = 0;
			foreach($this->tables as $table){
				if($table->isJoinTable() || !$simple_table_count) $from .= $table->toString() . ' ';
				else $from .= ', '.$table->toString() . ' ';
				$simple_table_count++;
			}
			
			$where = '';
			if(count($this->conditions) > 0){
				$where = 'WHERE ';
				foreach($this->conditions as $conditionGroup){
					$where .= $conditionGroup->toString();
				}
			}
			
			$groupBy = '';
			if($this->groups) if($this->groups[0] !== "")
				$groupBy = 'GROUP BY ' . implode(', ', $this->groups);
			
			$orderBy = '';
			if(count($this->orderby) > 0){
				$orderBy = 'ORDER BY ';
				foreach($this->orderby as $order){
					$orderBy .= $order->toString() .', ';
				}
				$orderBy = substr($orderBy, 0, -2);
			}
		 	$limit = '';
			if(count($this->limit) > 0){
				$limit = 'limit ';
				$limit .= $this->limit->toString();
			}

			$query =  $select . ' ' . $from . ' ' . $where . ' ' . $groupBy . ' ' . $orderBy . ' ' . $limit;
			return $query;
									
		}
	}



?>