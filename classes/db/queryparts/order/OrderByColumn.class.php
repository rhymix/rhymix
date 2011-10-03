<?php 
	class OrderByColumn {
		var $column_name;
		var $sort_order;
		
		function OrderByColumn($column_name, $sort_order){
			$this->column_name = $column_name;
			$this->sort_order = $sort_order;
		}
		
		function toString(){
			$result = $this->getColumnName();
			$result .= ' ';
			$result .= is_a($this->sort_order, 'Argument') ? $this->sort_order->getValue() : $this->sort_order;
			return $result;
		}
		
		function getColumnName(){
		    return is_a($this->column_name, 'Argument') ? $this->column_name->getValue() : $this->column_name;
		}
		
		function getArguments(){
			$args = array();
			if(is_a($this->column_name, 'Argument'))
				$args[]= $this->column_name;
			if(is_a($this->sort_order, 'Argument'))
				$args[] = $this->sort_order; 
		}
	}

?>